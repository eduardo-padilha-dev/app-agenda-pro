<?php

use App\Domain\Scheduling\AppointmentConflictChecker;
use App\Enums\AppointmentStatus;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->client = Client::factory()->create();
    $this->professional = Professional::create(['name' => 'Ana']);
    $this->service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $this->professional->services()->attach($this->service);
    $this->professional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);
});

function schedulingItem(string $start, string $end, ?Service $service = null, ?Professional $professional = null): array
{
    return [
        'service_id' => ($service ?? test()->service)->id,
        'professional_id' => ($professional ?? test()->professional)->id,
        'starts_at' => CarbonImmutable::parse($start, 'America/Sao_Paulo')->utc(),
        'ends_at' => CarbonImmutable::parse($end, 'America/Sao_Paulo')->utc(),
    ];
}

function assertSchedulingError(Client $client, array $items, string $field, ?int $ignoring = null): void
{
    try {
        app(AppointmentConflictChecker::class)->assertAvailable($client, $items, $ignoring);
        test()->fail('Expected a scheduling error.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }
}

function storedAppointmentItem(Client $client, Professional $professional, Service $service, string $start, string $end, AppointmentStatus $status = AppointmentStatus::Scheduled): int
{
    $appointment = $client->appointments()->create(['status' => $status]);
    $item = schedulingItem($start, $end, $service, $professional);
    $appointment->items()->create([
        ...$item,
        'service_name' => $service->name,
        'duration_minutes' => $service->duration_minutes,
        'price_cents' => $service->price_cents,
    ]);

    return $appointment->id;
}

test('accepts a valid slot and adjacent items for the same client', function () {
    app(AppointmentConflictChecker::class)->assertAvailable($this->client, [
        schedulingItem('2026-10-12 09:00', '2026-10-12 10:00'),
        schedulingItem('2026-10-12 10:00', '2026-10-12 11:00'),
    ]);
    expect(true)->toBeTrue();
});

test('rejects inactive clients, services, professionals and unrelated services', function (string $condition, string $field) {
    $item = schedulingItem('2026-10-12 09:00', '2026-10-12 10:00');
    match ($condition) {
        'client' => $this->client->delete(),
        'service' => $this->service->update(['active' => false]),
        'professional' => $this->professional->update(['active' => false]),
        'association' => $this->professional->services()->detach($this->service),
    };

    assertSchedulingError($this->client, [$item], $field);
})->with([
    'client' => ['client', 'client_id'],
    'service' => ['service', 'items.0.service_id'],
    'professional' => ['professional', 'items.0.professional_id'],
    'association' => ['association', 'items.0.service_id'],
]);

test('rejects reversed times, wrong duration and slots outside a single availability interval', function (Closure $itemFactory, string $field) {
    assertSchedulingError($this->client, [$itemFactory()], $field);
})->with([
    'reversed' => [fn () => schedulingItem('2026-10-12 10:00', '2026-10-12 09:00'), 'items.0.ends_at'],
    'duration' => [fn () => schedulingItem('2026-10-12 09:00', '2026-10-12 09:30'), 'items.0.ends_at'],
    'before hours' => [fn () => schedulingItem('2026-10-12 08:00', '2026-10-12 09:00'), 'items.0.starts_at'],
    'other day' => [fn () => schedulingItem('2026-10-13 09:00', '2026-10-13 10:00'), 'items.0.starts_at'],
]);

test('a break between availability intervals cannot contain an appointment', function () {
    $this->professional->availabilities()->delete();
    $this->professional->availabilities()->createMany([
        ['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00'],
        ['weekday' => 1, 'starts_at' => '13:00', 'ends_at' => '18:00'],
    ]);

    assertSchedulingError($this->client, [schedulingItem('2026-10-12 11:30', '2026-10-12 12:30')], 'items.0.starts_at');
    app(AppointmentConflictChecker::class)->assertAvailable($this->client, [schedulingItem('2026-10-12 13:00', '2026-10-12 14:00')]);
    expect(true)->toBeTrue();
});

test('blocks reject overlapping slots but allow adjacent slots', function () {
    $this->professional->blocks()->create([
        'starts_at' => schedulingItem('2026-10-12 10:00', '2026-10-12 11:00')['starts_at'],
        'ends_at' => schedulingItem('2026-10-12 10:00', '2026-10-12 11:00')['ends_at'],
    ]);

    assertSchedulingError($this->client, [schedulingItem('2026-10-12 09:30', '2026-10-12 10:30')], 'items.0.starts_at');
    app(AppointmentConflictChecker::class)->assertAvailable($this->client, [schedulingItem('2026-10-12 09:00', '2026-10-12 10:00')]);
    expect(true)->toBeTrue();
});

test('an existing item blocks its professional and client', function () {
    storedAppointmentItem($this->client, $this->professional, $this->service, '2026-10-12 09:00', '2026-10-12 10:00');
    $otherClient = Client::factory()->create();
    $otherProfessional = Professional::create(['name' => 'Bia']);
    $otherProfessional->services()->attach($this->service);
    $otherProfessional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);

    assertSchedulingError($otherClient, [schedulingItem('2026-10-12 09:00', '2026-10-12 10:00')], 'items.0.professional_id');
    assertSchedulingError($this->client, [schedulingItem('2026-10-12 09:00', '2026-10-12 10:00', professional: $otherProfessional)], 'items.0.starts_at');
    app(AppointmentConflictChecker::class)->assertAvailable($otherClient, [schedulingItem('2026-10-12 09:00', '2026-10-12 10:00', professional: $otherProfessional)]);
    expect(true)->toBeTrue();
});

test('cancelled and absent appointments release time but completed ones retain it', function (AppointmentStatus $status, bool $blocks) {
    storedAppointmentItem($this->client, $this->professional, $this->service, '2026-10-12 09:00', '2026-10-12 10:00', $status);
    $otherClient = Client::factory()->create();
    $item = schedulingItem('2026-10-12 09:00', '2026-10-12 10:00');

    if ($blocks) {
        assertSchedulingError($otherClient, [$item], 'items.0.professional_id');
    } else {
        app(AppointmentConflictChecker::class)->assertAvailable($otherClient, [$item]);
        expect(true)->toBeTrue();
    }
})->with([
    'scheduled' => [AppointmentStatus::Scheduled, true],
    'confirmed' => [AppointmentStatus::Confirmed, true],
    'completed' => [AppointmentStatus::Completed, true],
    'cancelled' => [AppointmentStatus::Cancelled, false],
    'no show' => [AppointmentStatus::NoShow, false],
]);

test('items in one request cannot overlap even with different professionals', function () {
    $otherProfessional = Professional::create(['name' => 'Bia']);
    $otherProfessional->services()->attach($this->service);
    $otherProfessional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);

    assertSchedulingError($this->client, [
        schedulingItem('2026-10-12 09:00', '2026-10-12 10:00'),
        schedulingItem('2026-10-12 09:30', '2026-10-12 10:30', professional: $otherProfessional),
    ], 'items.1.starts_at');
});

test('rescheduling may ignore its own old items while respecting other appointments', function () {
    $own = storedAppointmentItem($this->client, $this->professional, $this->service, '2026-10-12 09:00', '2026-10-12 10:00');
    $item = schedulingItem('2026-10-12 09:00', '2026-10-12 10:00');

    assertSchedulingError($this->client, [$item], 'items.0.professional_id');
    app(AppointmentConflictChecker::class)->assertAvailable($this->client, [$item], $own);

    $otherClient = Client::factory()->create();
    storedAppointmentItem($otherClient, $this->professional, $this->service, '2026-10-12 10:00', '2026-10-12 11:00');
    assertSchedulingError($this->client, [schedulingItem('2026-10-12 10:00', '2026-10-12 11:00')], 'items.0.professional_id', $own);
});

test('rescheduling cannot ignore an appointment belonging to another client', function () {
    $otherClient = Client::factory()->create();
    $otherAppointment = storedAppointmentItem($otherClient, $this->professional, $this->service, '2026-10-12 09:00', '2026-10-12 10:00');

    assertSchedulingError($this->client, [schedulingItem('2026-10-12 09:00', '2026-10-12 10:00')], 'appointment_id', $otherAppointment);
});
