<?php

use App\Actions\Scheduling\CreateAppointment;
use App\Actions\Scheduling\RescheduleAppointment;
use App\Enums\AppointmentStatus;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function reschedulingFixture(): array
{
    $client = Client::factory()->create(['name' => 'Ana Rodrigues']);
    $professional = Professional::create(['name' => 'Bia Costa']);
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $professional->services()->attach($service);
    $professional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);
    $appointment = app(CreateAppointment::class)->handle([
        'client_id' => $client->id,
        'notes' => 'Observação original',
        'items' => [['service_id' => $service->id, 'professional_id' => $professional->id, 'starts_at' => '2026-10-12T09:00']],
    ]);

    return compact('client', 'professional', 'service', 'appointment');
}

test('authenticated users see the current immutable client notes and items in the edit form', function () {
    $this->withoutVite();
    ['client' => $client, 'professional' => $professional, 'service' => $service, 'appointment' => $appointment] = reschedulingFixture();

    $response = $this->actingAs(User::factory()->create())->get(route('appointments.edit', $appointment));

    $response->assertOk()
        ->assertSee('Reagendar atendimento')
        ->assertSee($client->name)
        ->assertSee('Observação original')
        ->assertSee('value="'.$service->id.'" selected', false)
        ->assertSee('value="'.$professional->id.'" selected', false)
        ->assertSee('value="2026-10-12T09:00"', false)
        ->assertDontSee('name="client_id"', false);
});

test('replaces every item snapshot and returns a confirmed appointment to scheduled', function () {
    ['client' => $client, 'appointment' => $appointment] = reschedulingFixture();
    $appointment->update(['status' => AppointmentStatus::Confirmed]);
    $oldItemId = $appointment->items()->value('id');
    $newProfessional = Professional::create(['name' => 'Carla Lima']);
    $newService = Service::create(['name' => 'Escova', 'duration_minutes' => 30, 'price_cents' => 4500]);
    $newProfessional->services()->attach($newService);
    $newProfessional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);

    app(RescheduleAppointment::class)->handle($appointment, [
        'notes' => 'Nova observação',
        'items' => [['service_id' => $newService->id, 'professional_id' => $newProfessional->id, 'starts_at' => '2026-10-12T11:00']],
    ]);

    expect($appointment->fresh()->client_id)->toBe($client->id)
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->fresh()->notes)->toBe('Nova observação');
    $this->assertDatabaseMissing('appointment_items', ['id' => $oldItemId]);
    $this->assertDatabaseHas('appointment_items', [
        'appointment_id' => $appointment->id,
        'service_id' => $newService->id,
        'professional_id' => $newProfessional->id,
        'starts_at' => '2026-10-12 14:00:00',
        'ends_at' => '2026-10-12 14:30:00',
        'service_name' => 'Escova',
        'duration_minutes' => 30,
        'price_cents' => 4500,
    ]);
});

test('keeps all original data when any replacement item conflicts', function () {
    ['appointment' => $appointment, 'professional' => $professional, 'service' => $service] = reschedulingFixture();
    $appointment->update(['status' => AppointmentStatus::Confirmed]);
    $originalItem = $appointment->items()->first();
    $otherClient = Client::factory()->create();
    app(CreateAppointment::class)->handle([
        'client_id' => $otherClient->id,
        'items' => [['service_id' => $service->id, 'professional_id' => $professional->id, 'starts_at' => '2026-10-12T11:00']],
    ]);

    try {
        app(RescheduleAppointment::class)->handle($appointment, [
            'notes' => 'Não deve persistir',
            'items' => [
                ['service_id' => $service->id, 'professional_id' => $professional->id, 'starts_at' => '2026-10-12T10:00'],
                ['service_id' => $service->id, 'professional_id' => $professional->id, 'starts_at' => '2026-10-12T11:00'],
            ],
        ]);
        $this->fail('Expected a scheduling conflict.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('items.1.professional_id');
    }

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->fresh()->notes)->toBe('Observação original')
        ->and($appointment->fresh()->items)->toHaveCount(1);
    $this->assertDatabaseHas('appointment_items', [
        'id' => $originalItem->id,
        'appointment_id' => $appointment->id,
        'starts_at' => '2026-10-12 12:00:00',
        'ends_at' => '2026-10-12 13:00:00',
    ]);
});

test('may keep its own time but cannot reschedule a final appointment', function () {
    ['appointment' => $appointment, 'professional' => $professional, 'service' => $service] = reschedulingFixture();

    app(RescheduleAppointment::class)->handle($appointment, [
        'notes' => null,
        'items' => [['service_id' => $service->id, 'professional_id' => $professional->id, 'starts_at' => '2026-10-12T09:00']],
    ]);
    expect($appointment->fresh()->items)->toHaveCount(1);

    $appointment->update(['status' => AppointmentStatus::Completed]);
    expect(fn () => app(RescheduleAppointment::class)->handle($appointment, [
        'items' => [['service_id' => $service->id, 'professional_id' => $professional->id, 'starts_at' => '2026-10-12T10:00']],
    ]))->toThrow(ValidationException::class);
    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed)
        ->and($appointment->fresh()->items->first()->starts_at->format('Y-m-d H:i:s'))->toBe('2026-10-12 12:00:00');
});

test('update routes require authentication and reject invalid input without changes', function () {
    ['appointment' => $appointment] = reschedulingFixture();

    $this->get(route('appointments.edit', $appointment))->assertRedirect(route('login'));
    $this->put(route('appointments.update', $appointment), [])->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->from(route('appointments.edit', $appointment))
        ->put(route('appointments.update', $appointment), ['notes' => 'Alterada', 'items' => []])
        ->assertRedirect(route('appointments.edit', $appointment))
        ->assertSessionHasErrors('items');
    expect($appointment->fresh()->notes)->toBe('Observação original')
        ->and($appointment->fresh()->items)->toHaveCount(1);
});

test('final appointments cannot open the rescheduling form', function () {
    $this->withoutVite();
    ['appointment' => $appointment] = reschedulingFixture();
    $appointment->update(['status' => AppointmentStatus::Completed]);

    $this->actingAs(User::factory()->create())
        ->from(route('appointments.index'))
        ->get(route('appointments.edit', $appointment))
        ->assertRedirect(route('appointments.index'))
        ->assertSessionHasErrors('status');
});
