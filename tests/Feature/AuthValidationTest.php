<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login displays required field validation in the flash area', function () {
    $this->withoutVite()
        ->from('/login')
        ->followingRedirects()
        ->post('/login', ['email' => '', 'password' => ''])
        ->assertOk()
        ->assertSee('Preencha o e-mail.')
        ->assertSee('novalidate', false);
});

test('home redirects guests to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('successful login redirects to the originally requested agenda', function () {
    User::factory()->create(['email' => 'staff@example.test']);

    $this->get(route('calendar.index'))->assertRedirect(route('login'));

    $this->post('/login', [
        'email' => 'staff@example.test',
        'password' => 'password',
    ])->assertRedirect(route('calendar.index'));

    $this->assertAuthenticated();
});

test('invalid credentials return a flash error', function () {
    User::factory()->create(['email' => 'staff@example.test']);

    $this->from('/login')->post('/login', [
        'email' => 'staff@example.test',
        'password' => 'wrong-password',
    ])->assertRedirect('/login')->assertSessionHas('error', 'E-mail ou senha inválidos.');

    $this->assertGuest();
});

test('logout clears the authenticated session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('authenticated users visiting home are sent to the agenda', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('calendar.index'));
});
