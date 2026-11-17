<?php

use App\Actions\Scheduling\CreateAppointment;
use App\Enums\AppointmentStatus;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->client = Client::factory()->create(['name' => 'Ana Rodrigues']);
    $this->professional = Professional::create(['name' => 'Bia Costa']);
    $this->service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $this->professional->services()->attach($this->service);
    $this->professional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);
});

test('creates an appointment with UTC items and service snapshots atomically', function () {
    $otherService = Service::create(['name' => 'Escova', 'duration_minutes' => 30, 'price_cents' => 4500]);
    $this->professional->services()->attach($otherService);

    $appointment = app(CreateAppointment::class)->handle([
        'client_id' => $this->client->id,
        'notes' => 'Primeira visita',
        'items' => [
            ['service_id' => $this->service->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T09:00'],
            ['service_id' => $otherService->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T10:00'],
        ],
    ]);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->fresh()->items()->count())->toBe(2);
    $this->assertDatabaseHas('appointment_items', [
        'appointment_id' => $appointment->id,
        'service_id' => $this->service->id,
        'starts_at' => '2026-10-12 12:00:00',
        'ends_at' => '2026-10-12 13:00:00',
        'service_name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000,
    ]);

    $this->service->update(['name' => 'Corte premium', 'price_cents' => 12000]);
    expect($appointment->fresh()->items->first()->service_name)->toBe('Corte')
        ->and($appointment->fresh()->items->first()->price_cents)->toBe(8000);
});

test('a conflict in a later item leaves no partial appointment', function () {
    $this->professional->blocks()->create([
        'starts_at' => '2026-10-12 14:00:00', 'ends_at' => '2026-10-12 15:00:00',
    ]);

    try {
        app(CreateAppointment::class)->handle([
            'client_id' => $this->client->id,
            'items' => [
                ['service_id' => $this->service->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T09:00'],
                ['service_id' => $this->service->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T11:00'],
            ],
        ]);
        $this->fail('Expected a conflict.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('items.1.starts_at');
    }

    $this->assertDatabaseCount('appointments', 0);
    $this->assertDatabaseCount('appointment_items', 0);
});

test('the appointment form and store route require authentication', function () {
    $this->get(route('appointments.create'))->assertRedirect(route('login'));
    $this->post(route('appointments.store'), [])->assertRedirect(route('login'));
});

test('authenticated users can create and view a real appointment', function () {
    $this->actingAs(User::factory()->create())->get(route('appointments.create'))
        ->assertOk()->assertSee('Ana Rodrigues')->assertSee('Corte');

    $this->post(route('appointments.store'), [
        'client_id' => $this->client->id,
        'items' => [['service_id' => $this->service->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T09:00']],
    ])->assertRedirect(route('appointments.index'))->assertSessionHasNoErrors()->assertSessionHas('success');

    $this->get(route('appointments.index'))->assertOk()->assertSee('Ana Rodrigues')->assertSee('Corte')->assertSee('R$ 80,00');
    $this->assertDatabaseCount('appointments', 1);
});

test('calendar creation links open the real form with the selected local time', function () {
    $this->actingAs(User::factory()->create())->get(route('calendar.index'))
        ->assertOk()
        ->assertSee('href="'.route('appointments.create').'"', false)
        ->assertSee('data-create-appointment-url="'.route('appointments.create').'"', false);

    $this->get(route('appointments.create', ['starts_at' => '2026-10-12T09:00']))
        ->assertOk()->assertSee('value="2026-10-12T09:00"', false);
});

test('invalid form input and domain conflicts return field errors without saving', function (array $changes, string $field) {
    $data = [
        'client_id' => $this->client->id,
        'items' => [['service_id' => $this->service->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T09:00']],
    ];
    $data = array_replace_recursive($data, $changes);
    if (array_key_exists('items', $changes) && $changes['items'] === []) {
        $data['items'] = [];
    }

    $this->actingAs(User::factory()->create())->from(route('appointments.create'))
        ->post(route('appointments.store'), $data)
        ->assertRedirect(route('appointments.create'))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('appointments', 0);
})->with([
    'missing client' => [['client_id' => ''], 'client_id'],
    'no items' => [['items' => []], 'items'],
    'invalid time' => [['items' => [['starts_at' => 'wrong']]], 'items.0.starts_at'],
    'outside availability' => [['items' => [['starts_at' => '2026-10-12T08:00']]], 'items.0.starts_at'],
]);
