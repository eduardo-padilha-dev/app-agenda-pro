<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prototype screens render their content without database setup', function (string $page, string $content) {
    $this->withoutVite()
        ->get('/prototipo/'.$page)
        ->assertOk()
        ->assertSee($content)
        ->assertSee('Protótipo');
})->with([
    ['login', 'Acesso restrito'],
    ['calendar', 'Novo agendamento'],
    ['agendamentos', 'Ana Rodrigues'],
    ['clientes', 'Ana Rodrigues'],
    ['profissionais', 'Camila Santos'],
    ['servicos', 'Maquiagem Completa'],
]);

test('agenda route no longer renders prototype records', function () {
    $this->withoutMiddleware()
        ->withoutVite()
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertSee('Novo agendamento')
        ->assertDontSee('Ana Rodrigues');
});

test('calendar navigation stays on the authenticated calendar route', function () {
    $this->withoutMiddleware()->withoutVite()->get(route('calendar.index', ['date' => '2026-09-22']))
        ->assertOk()
        ->assertSee('href="'.route('calendar.index', ['date' => '2026-09-15']).'"', false)
        ->assertSee('href="'.route('calendar.index').'"', false)
        ->assertDontSee('href="/prototipo/calendar', false);
});

test('prototype calendar date links remain in the preview', function () {
    $this->withoutVite()->get(route('prototype', ['page' => 'calendar', 'date' => '2026-09-22']))
        ->assertOk()
        ->assertSee('href="'.route('prototype', ['page' => 'calendar', 'date' => '2026-09-15']).'"', false);
});

test('agenda route requires authentication', function () {
    $this->get(route('calendar.index'))->assertRedirect(route('login'));
});

test('unknown prototype pages are not resolved as arbitrary views', function () {
    $this->get('/prototipo/unknown')->assertNotFound();
});

test('prototype is unavailable in production', function () {
    $this->app->instance('env', 'production');

    $this->get('/prototipo/calendar')->assertNotFound();
});

test('calendar navigation selects the requested day without moving demo appointments', function () {
    app()->setLocale('en');
    $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00', 'America/Sao_Paulo'));

    $this->withoutVite()->get('/prototipo/calendar?date=2026-09-22')
        ->assertOk()
        ->assertViewHas('date', fn ($date) => $date->format('Y-m-d') === '2026-09-22')
        ->assertViewHas('week', fn ($week) => $week->format('Y-m-d') === '2026-09-21')
        ->assertViewHas('appointments', fn ($appointments) => $appointments[0]['items'][0]['startTime'] === '2026-09-14T09:00:00-03:00');
});

test('invalid calendar dates are rejected', function () {
    $this->getJson('/prototipo/calendar?date=2026-02-30')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
});
