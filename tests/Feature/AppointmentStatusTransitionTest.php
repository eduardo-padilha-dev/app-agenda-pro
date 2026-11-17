<?php

use App\Actions\Scheduling\ChangeAppointmentStatus;
use App\Actions\Scheduling\CreateAppointment;
use App\Domain\Scheduling\AppointmentConflictChecker;
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
    $this->appointment = app(CreateAppointment::class)->handle([
        'client_id' => $this->client->id,
        'items' => [['service_id' => $this->service->id, 'professional_id' => $this->professional->id, 'starts_at' => '2026-10-12T09:00']],
    ]);
});

test('valid appointment status transitions succeed', function (AppointmentStatus $from, AppointmentStatus $to) {
    $this->appointment->update(['status' => $from]);

    app(ChangeAppointmentStatus::class)->handle($this->appointment, $to);

    expect($this->appointment->fresh()->status)->toBe($to);
})->with([
    'scheduled to confirmed' => [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed],
    'scheduled to completed' => [AppointmentStatus::Scheduled, AppointmentStatus::Completed],
    'scheduled to cancelled' => [AppointmentStatus::Scheduled, AppointmentStatus::Cancelled],
    'scheduled to no show' => [AppointmentStatus::Scheduled, AppointmentStatus::NoShow],
    'confirmed to completed' => [AppointmentStatus::Confirmed, AppointmentStatus::Completed],
    'confirmed to cancelled' => [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled],
    'confirmed to no show' => [AppointmentStatus::Confirmed, AppointmentStatus::NoShow],
]);

test('invalid or repeated transitions leave the status unchanged', function (AppointmentStatus $from, AppointmentStatus $to) {
    $this->appointment->update(['status' => $from]);

    try {
        app(ChangeAppointmentStatus::class)->handle($this->appointment, $to);
        $this->fail('Expected invalid transition.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('status');
    }

    expect($this->appointment->fresh()->status)->toBe($from);
})->with([
    'repeat confirmed' => [AppointmentStatus::Confirmed, AppointmentStatus::Confirmed],
    'confirmed to scheduled' => [AppointmentStatus::Confirmed, AppointmentStatus::Scheduled],
    'completed to cancelled' => [AppointmentStatus::Completed, AppointmentStatus::Cancelled],
    'cancelled to confirmed' => [AppointmentStatus::Cancelled, AppointmentStatus::Confirmed],
    'no show to completed' => [AppointmentStatus::NoShow, AppointmentStatus::Completed],
]);

test('cancellation stores its UTC timestamp and optional reason', function () {
    $this->travelTo('2026-10-11 15:00:00');

    app(ChangeAppointmentStatus::class)->handle($this->appointment, AppointmentStatus::Cancelled, 'Pedido da cliente');

    $this->assertDatabaseHas('appointments', [
        'id' => $this->appointment->id,
        'status' => 'cancelled',
        'cancelled_at' => '2026-10-11 15:00:00',
        'cancellation_reason' => 'Pedido da cliente',
    ]);
});

test('cancellation and absence release the professional while completion retains the slot', function (AppointmentStatus $status, bool $available) {
    app(ChangeAppointmentStatus::class)->handle($this->appointment, $status);
    $otherClient = Client::factory()->create();
    $item = [
        'service_id' => $this->service->id,
        'professional_id' => $this->professional->id,
        'starts_at' => $this->appointment->items->first()->starts_at,
        'ends_at' => $this->appointment->items->first()->ends_at,
    ];

    if ($available) {
        app(AppointmentConflictChecker::class)->assertAvailable($otherClient, [$item]);
        expect(true)->toBeTrue();
    } else {
        expect(fn () => app(AppointmentConflictChecker::class)->assertAvailable($otherClient, [$item]))
            ->toThrow(ValidationException::class);
    }
})->with([
    'cancelled' => [AppointmentStatus::Cancelled, true],
    'no show' => [AppointmentStatus::NoShow, true],
    'completed' => [AppointmentStatus::Completed, false],
]);

test('status routes require authentication', function (string $route) {
    $this->post(route($route, $this->appointment))->assertRedirect(route('login'));
    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled);
})->with(['appointments.confirm', 'appointments.complete', 'appointments.cancel', 'appointments.no-show']);

test('authenticated users can change status from the real appointment list', function () {
    $this->actingAs(User::factory()->create())->get(route('appointments.index'))
        ->assertOk()->assertSee('Confirmar')->assertSee('Concluir')->assertSee('Cancelar')->assertSee('Não compareceu');

    $this->post(route('appointments.confirm', $this->appointment))
        ->assertRedirect(route('appointments.index'))->assertSessionHasNoErrors();
    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);

    $this->post(route('appointments.cancel', $this->appointment), ['cancellation_reason' => 'Remarcou'])
        ->assertRedirect(route('appointments.index'))->assertSessionHasNoErrors();
    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($this->appointment->fresh()->cancellation_reason)->toBe('Remarcou');
});

test('appointment actions use centered confirmation dialogs instead of browser confirms', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('appointments.index'));

    $response
        ->assertSee('data-open="complete-appointment-'.$this->appointment->id.'"', false)
        ->assertSee('data-open="no-show-appointment-'.$this->appointment->id.'"', false)
        ->assertSee('data-open="cancel-appointment-'.$this->appointment->id.'"', false)
        ->assertSee('id="complete-appointment-'.$this->appointment->id.'"', false)
        ->assertSee('id="no-show-appointment-'.$this->appointment->id.'"', false)
        ->assertSee('id="cancel-appointment-'.$this->appointment->id.'"', false)
        ->assertDontSee('onclick="return confirm(', false);
});

test('invalid cancellation reason and final statuses are rejected over HTTP', function () {
    $this->actingAs(User::factory()->create())->from(route('appointments.index'))
        ->post(route('appointments.cancel', $this->appointment), ['cancellation_reason' => ['invalid']])
        ->assertSessionHasErrors('cancellation_reason');
    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled);

    app(ChangeAppointmentStatus::class)->handle($this->appointment, AppointmentStatus::Cancelled);
    $this->post(route('appointments.complete', $this->appointment))->assertSessionHasErrors('status');
    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});
