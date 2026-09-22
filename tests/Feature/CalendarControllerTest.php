<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

function createCalendarAppointment(
    string $clientName,
    string $professionalName,
    string $startsAt,
    AppointmentStatus $status = AppointmentStatus::Scheduled,
): Appointment {
    $client = Client::factory()->create(['name' => $clientName]);
    $professional = Professional::create(['name' => $professionalName]);
    $service = Service::create([
        'name' => 'Serviço '.$clientName,
        'duration_minutes' => 60,
        'price_cents' => 8000,
    ]);
    $appointment = $client->appointments()->create([
        'status' => $status,
        'notes' => 'Observação de '.$clientName,
    ]);
    $appointment->items()->create([
        'service_id' => $service->id,
        'professional_id' => $professional->id,
        'starts_at' => $startsAt,
        'ends_at' => CarbonImmutable::parse($startsAt)->addHour(),
        'service_name' => $service->name,
        'duration_minutes' => 60,
        'price_cents' => 8000,
    ]);

    return $appointment;
}

test('agenda route renders real records from the requested week', function () {
    createCalendarAppointment('Cliente da semana', 'Profissional real', '2026-09-21 12:00:00');
    createCalendarAppointment('Cliente de outra semana', 'Profissional distante', '2026-09-28 12:00:00');

    $this->get(route('calendar.index', ['date' => '2026-09-23']))
        ->assertOk()
        ->assertSee('Cliente da semana')
        ->assertSee('Profissional real')
        ->assertDontSee('Cliente de outra semana')
        ->assertDontSee('Ana Rodrigues')
        ->assertViewHas('date', fn (CarbonImmutable $date): bool => $date->format('Y-m-d') === '2026-09-23')
        ->assertViewHas('week', fn (CarbonImmutable $week): bool => $week->format('Y-m-d') === '2026-09-21');
});

test('agenda converts stored UTC instants to the business timezone', function () {
    createCalendarAppointment('Cliente timezone', 'Profissional timezone', '2026-09-21 12:30:00');

    $this->get(route('calendar.index', ['date' => '2026-09-21']))
        ->assertOk()
        ->assertSee('09:30 – 10:30');
});

test('agenda expands the grid to include appointments outside prototype hours', function () {
    createCalendarAppointment('Cliente cedo', 'Profissional cedo', '2026-09-21 10:00:00');

    $this->get(route('calendar.index', ['date' => '2026-09-21']))
        ->assertOk()
        ->assertSee('07:00 – 08:00')
        ->assertSee('>07:00</span>', false)
        ->assertSee('top: 1px', false)
        ->assertDontSee('top: -63px', false);
});

test('agenda renders overlapping appointments and real professional filters', function () {
    createCalendarAppointment('Cliente A', 'Profissional A', '2026-09-21 12:00:00');
    createCalendarAppointment('Cliente B', 'Profissional B', '2026-09-21 12:00:00');
    $inactiveAppointment = createCalendarAppointment('Cliente C', 'Profissional inativa', '2026-09-21 15:00:00');
    $inactiveAppointment->items->first()->professional->update(['active' => false]);

    $this->get(route('calendar.index', ['date' => '2026-09-21']))
        ->assertOk()
        ->assertSee('Cliente A')
        ->assertSee('Cliente B')
        ->assertSee('Profissional A')
        ->assertSee('Profissional B')
        ->assertSee('data-agenda-filter="'.$inactiveAppointment->items->first()->professional_id.'"', false)
        ->assertSee('left: calc(0% + 2px)', false)
        ->assertSee('left: calc(50% + 2px)', false);
});

test('agenda displays overlapping blocks using local dates and times', function () {
    $professional = Professional::create(['name' => 'Profissional bloqueada']);
    $professional->blocks()->create([
        'starts_at' => '2026-09-22 02:00:00',
        'ends_at' => '2026-09-22 12:00:00',
        'reason' => 'Curso externo',
    ]);
    $professional->blocks()->create([
        'starts_at' => '2026-09-24 02:00:00',
        'ends_at' => '2026-09-24 03:00:00',
        'reason' => 'Bloqueio até meia-noite',
    ]);

    $this->get(route('calendar.index', ['date' => '2026-09-22']))
        ->assertOk()
        ->assertSee('Curso externo')
        ->assertSee('Profissional bloqueada')
        ->assertSee('Bloqueio até meia-noite')
        ->assertSee('top: 0px; height: 576px', false)
        ->assertSee('top: 1472px; height: 64px', false);
});

test('cancelled and no show appointments do not occupy calendar time', function (AppointmentStatus $status) {
    createCalendarAppointment('Cliente removida da grade', 'Profissional indisponível', '2026-09-21 12:00:00', $status);

    $this->get(route('calendar.index', ['date' => '2026-09-21']))
        ->assertOk()
        ->assertDontSee('Cliente removida da grade');
})->with([
    'cancelled' => AppointmentStatus::Cancelled,
    'no show' => AppointmentStatus::NoShow,
]);

test('calendar cards open real appointment details and actions', function () {
    $appointment = createCalendarAppointment('Cliente detalhes', 'Profissional detalhes', '2026-09-21 12:00:00');

    $this->get(route('calendar.index', ['date' => '2026-09-21']))
        ->assertOk()
        ->assertSee('data-open="appointment-'.$appointment->id.'"', false)
        ->assertSee('Observação de Cliente detalhes')
        ->assertSee('action="'.route('appointments.confirm', $appointment).'"', false)
        ->assertSee('Agendado')
        ->assertSee('href="'.route('appointments.edit', $appointment).'"', false);
});

test('calendar navigation remains on the real route', function () {
    $this->get(route('calendar.index', ['date' => '2026-09-23']))
        ->assertOk()
        ->assertSee('href="'.route('calendar.index', ['date' => '2026-09-16']).'"', false)
        ->assertSee('href="'.route('calendar.index').'"', false)
        ->assertDontSee('href="/prototipo/calendar', false);
});

test('invalid calendar dates are rejected', function () {
    $this->get(route('calendar.index', ['date' => '2026-02-30']))
        ->assertSessionHasErrors('date');
});
