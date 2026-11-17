<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a client from its factory with the expected attributes', function () {
    $client = Client::factory()->create([
        'name' => 'Ana Rodrigues',
        'phone' => '11999990000',
        'email' => 'ana@example.test',
        'notes' => 'Prefere atendimento pela manhã.',
    ]);

    expect($client->name)->toBe('Ana Rodrigues')
        ->and($client->phone)->toBe('11999990000')
        ->and($client->email)->toBe('ana@example.test')
        ->and($client->notes)->toBe('Prefere atendimento pela manhã.')
        ->and($client->trashed())->toBeFalse();
});

it('soft deletes a client while preserving it in the database', function () {
    $client = Client::factory()->create();

    $client->delete();

    expect(Client::find($client->id))->toBeNull()
        ->and(Client::withTrashed()->find($client->id))->not->toBeNull()
        ->and(Client::withTrashed()->find($client->id)->trashed())->toBeTrue();
});
