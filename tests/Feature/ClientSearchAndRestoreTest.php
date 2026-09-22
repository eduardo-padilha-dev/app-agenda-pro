<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('clients can be searched by name or phone across pages', function (string $search) {
    Client::factory()->count(11)->create(['name' => 'Ana Silva', 'phone' => '21988880000']);
    $match = Client::factory()->create(['name' => 'Zelia Souza', 'phone' => '11977776666']);
    Client::factory()->create(['name' => 'Zelia inativa', 'phone' => '11977776666'])->delete();

    $this->actingAs(User::factory()->create())->get(route('clients.index', ['search' => $search]))
        ->assertOk()->assertSee('Zelia Souza')->assertDontSee('Ana Silva')->assertDontSee('Zelia inativa')
        ->assertViewHas('clients', fn ($clients) => $clients->total() === 1 && $clients->first()->id === $match->id);
})->with(['name' => 'zelia', 'phone' => '77776666']);

test('show inactive includes deactivated clients and offers restoration', function () {
    Client::factory()->create(['name' => 'Cliente ativo']);
    $inactive = Client::factory()->create(['name' => 'Cliente inativo']);
    $inactive->delete();

    $this->actingAs(User::factory()->create())->get(route('clients.index', ['show_inactive' => 1]))
        ->assertOk()->assertSee('Cliente ativo')->assertSee('Cliente inativo')->assertSee('Inativo')
        ->assertSee('Reativar')->assertSee('Ocultar inativos')
        ->assertDontSee('data-open="edit-client-'.$inactive->id.'"', false);
});

test('search combines with inactive filter and persists in pagination', function () {
    Client::factory()->count(11)->create(['name' => 'Ana Souza']);
    Client::factory()->create(['name' => 'Ana inativa'])->delete();
    Client::factory()->create(['name' => 'Outro cliente']);

    $this->actingAs(User::factory()->create())->get(route('clients.index', ['search' => 'Ana', 'show_inactive' => 1]))
        ->assertOk()->assertDontSee('Outro cliente')
        ->assertViewHas('clients', function ($clients) {
            parse_str(parse_url($clients->nextPageUrl(), PHP_URL_QUERY), $query);

            return $clients->total() === 12 && $query['search'] === 'Ana' && $query['show_inactive'] === '1';
        });
});

test('unmatched search displays a filtered empty state', function () {
    Client::factory()->create(['name' => 'Ana']);

    $this->actingAs(User::factory()->create())->get(route('clients.index', ['search' => 'Inexistente']))
        ->assertOk()->assertSee('Nenhum resultado para os filtros aplicados.')->assertDontSee('Nenhum cliente cadastrado.');
});

test('malformed search filters are rejected', function (array $query, string $field) {
    $this->actingAs(User::factory()->create())->getJson(route('clients.index', $query))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'array search' => [['search' => ['Ana']], 'search'],
    'long search' => [['search' => str_repeat('a', 256)], 'search'],
    'invalid inactive flag' => [['show_inactive' => 'invalid'], 'show_inactive'],
]);

test('authenticated user restores the original client without losing its data', function () {
    $client = Client::factory()->create(['name' => 'Cliente reativado', 'notes' => 'Histórico preservado']);
    $client->delete();
    $original = $client->getAttributes();

    $this->actingAs(User::factory()->create())->patch(route('clients.restore', $client))
        ->assertRedirect(route('clients.index'))->assertSessionHas('success');

    $this->assertDatabaseCount('clients', 1);
    $this->assertDatabaseHas('clients', [
        'id' => $client->id, 'name' => $original['name'], 'phone' => $original['phone'],
        'email' => $original['email'], 'notes' => $original['notes'], 'deleted_at' => null,
    ]);
    $this->get(route('clients.index'))->assertOk()->assertSee('Cliente reativado');
});

test('guests cannot restore clients', function () {
    $client = Client::factory()->create();
    $client->delete();

    $this->patch(route('clients.restore', $client))->assertRedirect(route('login'));

    $this->assertSoftDeleted($client);
});

test('restoring an active client returns 404 without changing it', function () {
    $client = Client::factory()->create();
    $original = $client->getAttributes();

    $this->actingAs(User::factory()->create())->patch(route('clients.restore', $client))->assertNotFound();

    $this->assertDatabaseHas('clients', $original);
});

test('restoring a missing client returns 404', function () {
    $this->actingAs(User::factory()->create())->patch(route('clients.restore', 999))->assertNotFound();

    $this->assertDatabaseCount('clients', 0);
});

test('name length validation reports the actual limit', function (string $method, string $route) {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())->from(route('clients.index'))
        ->{$method}(route($route, $client), ['name' => str_repeat('a', 256), 'phone' => '11999990000'])
        ->assertSessionHasErrors(['name' => 'O nome deve ter no máximo 255 caracteres.']);

    $this->assertDatabaseCount('clients', 1);
    $this->assertDatabaseHas('clients', $client->getAttributes());
})->with([['post', 'clients.store'], ['patch', 'clients.update']]);

test('name accepts accented characters at the maximum length', function (string $method, string $route) {
    $client = Client::factory()->create();
    $name = str_repeat('á', 255);

    $this->actingAs(User::factory()->create())->{$method}(route($route, $client), ['name' => $name, 'phone' => '11999990000'])
        ->assertRedirect(route('clients.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('clients', ['name' => $name, 'phone' => '11999990000']);
})->with([['post', 'clients.store'], ['patch', 'clients.update']]);
