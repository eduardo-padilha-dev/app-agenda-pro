<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

test('phone requires ten or eleven digits on creation and editing', function ($phone) {
    $client = Client::factory()->create();
    $data = ['name' => 'Ana', 'phone' => $phone];
    $this->post(route('clients.store'), $data)->assertSessionHasErrors('phone');
    $this->patch(route('clients.update', $client), $data)->assertSessionHasErrors('phone');
    $this->assertDatabaseCount('clients', 1);
    $this->assertDatabaseHas('clients', $client->getAttributes());
})->with(['---', '()', '1', '119999999', '5511999999999']);

test('phone search ignores formatting on either side', function ($stored, $search) {
    $client = Client::factory()->create(['phone' => $stored]);
    $this->get(route('clients.index', ['search' => $search]))
        ->assertOk()->assertViewHas('clients', fn ($clients) => $clients->total() === 1 && $clients->first()->id === $client->id);
})->with([
    ['(11) 99999-0000', '11999990000'],
    ['11999990000', '(11) 99999-0000'],
]);

test('failed edit does not populate the creation dialog', function () {
    $client = Client::factory()->create();
    $this->from(route('clients.index'))->patch(route('clients.update', $client), [
        'name' => 'Nome exclusivo da edição', 'phone' => '', '_editing_client' => $client->id,
    ])->assertSessionHasErrors('phone');
    $response = $this->get(route('clients.index'))->assertOk();
    preg_match('/<dialog id="create-client".*?<\/dialog>/s', $response->getContent(), $matches);
    expect($matches)->not->toBeEmpty();
    expect($matches[0])->not->toContain('Nome exclusivo da edição')->not->toContain('Informe o telefone.');
    $response->assertSee('Nome exclusivo da edição');
});

test('valid local phones are accepted on creation and editing', function ($phone) {
    $data = ['name' => 'Ana', 'phone' => $phone];
    $this->post(route('clients.store'), $data)->assertSessionHasNoErrors();
    $client = Client::firstOrFail();
    $this->patch(route('clients.update', $client), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('clients', $data);
})->with(['(11) 3333-4444', '11999990000']);

test('failed creation retains its own values and inline errors', function () {
    $this->from(route('clients.index'))->post(route('clients.store'), [
        'name' => 'Cadastro preservado', 'phone' => '',
    ])->assertSessionHasErrors('phone');
    $this->view('clients.create', ['errors' => session('errors')])
        ->assertSee('Cadastro preservado')->assertSee('Informe o telefone.');
});
