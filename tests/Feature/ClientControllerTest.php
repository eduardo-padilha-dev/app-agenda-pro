<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('client routes require authentication without changing records', function (string $method, string $route) {
    $client = Client::factory()->create(['name' => 'Cliente original']);

    $this->{$method}(route($route, $client), ['name' => 'Outro nome', 'phone' => '11999990000'])
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('clients', 1);
    $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Cliente original', 'deleted_at' => null]);
})->with([
    'list' => ['get', 'clients.index'],
    'create' => ['post', 'clients.store'],
    'update' => ['patch', 'clients.update'],
    'delete' => ['delete', 'clients.destroy'],
]);

test('client list is alphabetically ordered and excludes deactivated clients', function () {
    Client::factory()->create(['name' => 'Zelia Silva']);
    Client::factory()->create(['name' => 'Ana Souza']);
    Client::factory()->create(['name' => 'Cliente desativado'])->delete();

    $this->actingAs(User::factory()->create())->get(route('clients.index'))
        ->assertOk()
        ->assertSeeInOrder(['Ana Souza', 'Zelia Silva'])
        ->assertDontSee('Cliente desativado');
});

test('client list paginates records', function () {
    foreach (range(1, 11) as $number) {
        Client::factory()->create(['name' => sprintf('Cliente %02d', $number)]);
    }

    $this->actingAs(User::factory()->create())->get(route('clients.index'))
        ->assertOk()->assertSee('Cliente 01')->assertSee('Cliente 10')->assertDontSee('Cliente 11');

    $this->get(route('clients.index', ['page' => 2]))
        ->assertOk()->assertSee('Cliente 11')->assertDontSee('Cliente 01');
});

test('empty client list displays its empty state', function () {
    $this->actingAs(User::factory()->create())->get(route('clients.index'))
        ->assertOk()->assertSee('Nenhum cliente cadastrado.');
});

test('authenticated user can create a client', function () {
    $data = ['name' => 'Ana Souza', 'phone' => '(11) 99999-0000', 'email' => 'ana@example.test', 'notes' => 'Atender pela manhã.'];

    $this->actingAs(User::factory()->create())->post(route('clients.store'), $data)
        ->assertRedirect(route('clients.index'))->assertSessionHasNoErrors()->assertSessionHas('success');

    $this->assertDatabaseCount('clients', 1);
    $this->assertDatabaseHas('clients', [...$data, 'deleted_at' => null]);
});

test('client can be created without optional fields', function () {
    $this->actingAs(User::factory()->create())->post(route('clients.store'), ['name' => 'Ana Souza', 'phone' => '11999990000'])
        ->assertRedirect(route('clients.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('clients', ['name' => 'Ana Souza', 'phone' => '11999990000', 'email' => null, 'notes' => null]);
});

test('authenticated user can update a client and clear optional fields', function () {
    $client = Client::factory()->create(['email' => 'antigo@example.test', 'notes' => 'Antigas observações']);
    $other = Client::factory()->create(['name' => 'Outro cliente']);

    $this->actingAs(User::factory()->create())->patch(route('clients.update', $client), [
        'name' => 'Novo nome', 'phone' => '21988887777', 'email' => '', 'notes' => '',
    ])->assertRedirect(route('clients.index'))->assertSessionHasNoErrors()->assertSessionHas('success');

    $this->assertDatabaseCount('clients', 2);
    $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Novo nome', 'phone' => '21988887777', 'email' => null, 'notes' => null]);
    $this->assertDatabaseHas('clients', ['id' => $other->id, 'name' => 'Outro cliente']);
});

test('invalid client input is rejected without persisting changes', function (string $method, string $route, string $field, mixed $value) {
    $client = Client::factory()->create(['name' => 'Original']);
    $original = $client->getAttributes();
    $data = ['name' => 'Ana Souza', 'phone' => '11999990000', 'email' => 'ana@example.test', 'notes' => 'Observações'];
    $data[$field] = $value;

    $this->actingAs(User::factory()->create())->from(route('clients.index'))
        ->{$method}(route($route, $client), $data)
        ->assertRedirect(route('clients.index'))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('clients', 1);
    $this->assertDatabaseHas('clients', $original);
})->with([
    'create' => ['post', 'clients.store'],
    'update' => ['patch', 'clients.update'],
])->with([
    'missing name' => ['name', ''],
    'non-text name' => ['name', ['Ana']],
    'name without letters' => ['name', '123'],
    'long name' => ['name', str_repeat('a', 256)],
    'missing phone' => ['phone', ''],
    'non-text phone' => ['phone', ['11999990000']],
    'phone with letters' => ['phone', 'telefone'],
    'long phone' => ['phone', str_repeat('1', 256)],
    'invalid email' => ['email', 'invalido'],
    'long email' => ['email', str_repeat('a', 245).'@example.test'],
    'non-text notes' => ['notes', ['Observações']],
]);

test('failed edit preserves input and identifies the client to reopen', function () {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())->from(route('clients.index'))
        ->patch(route('clients.update', $client), ['name' => 'Nome alterado', 'phone' => '', '_editing_client' => $client->id])
        ->assertSessionHasErrors('phone')->assertSessionHasInput('name', 'Nome alterado')
        ->assertSessionHasInput('_editing_client', $client->id);

    $this->assertDatabaseHas('clients', $client->getAttributes());
});

test('deactivation preserves the client in the database and removes it from the list', function () {
    $client = Client::factory()->create(['name' => 'Cliente desativado']);

    $this->actingAs(User::factory()->create())->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'))->assertSessionHas('success');

    $this->assertSoftDeleted($client);
    $this->assertDatabaseCount('clients', 1);
    $this->get(route('clients.index'))->assertOk()->assertDontSee('Cliente desativado');
});

test('missing clients return 404 on writes', function (string $method, string $route) {
    $this->actingAs(User::factory()->create())->{$method}(route($route, 999), ['name' => 'Ana Souza', 'phone' => '11999990000'])
        ->assertNotFound();

    $this->assertDatabaseCount('clients', 0);
})->with([['patch', 'clients.update'], ['delete', 'clients.destroy']]);

test('deactivated clients cannot be updated or deleted again', function (string $method, string $route) {
    $client = Client::factory()->create(['name' => 'Nome preservado']);
    $client->delete();

    $this->actingAs(User::factory()->create())->{$method}(route($route, $client), ['name' => 'Outro nome', 'phone' => '11999990000'])
        ->assertNotFound();

    $this->assertSoftDeleted($client);
    $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Nome preservado']);
})->with([['patch', 'clients.update'], ['delete', 'clients.destroy']]);
