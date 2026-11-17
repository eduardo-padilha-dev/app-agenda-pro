<?php

use App\Models\Professional;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stores a professional with optional contact details and an active state', function () {
    $professional = Professional::create([
        'name' => 'Ana Rodrigues',
        'phone' => null,
        'email' => null,
        'notes' => null,
    ]);

    expect($professional->fresh()->name)->toBe('Ana Rodrigues')
        ->and($professional->fresh()->phone)->toBeNull()
        ->and($professional->fresh()->email)->toBeNull()
        ->and($professional->fresh()->notes)->toBeNull()
        ->and($professional->fresh()->active)->toBeTrue();

    $professional->update(['active' => false]);

    expect($professional->fresh()->active)->toBeFalse();
});

it('persists contact details and can reactivate a professional', function () {
    $professional = Professional::create([
        'name' => 'Bruno Silva',
        'phone' => '11999990000',
        'email' => 'bruno@example.test',
        'notes' => 'Atende aos sábados.',
        'active' => false,
    ]);

    expect($professional->fresh()->phone)->toBe('11999990000')
        ->and($professional->fresh()->email)->toBe('bruno@example.test')
        ->and($professional->fresh()->notes)->toBe('Atende aos sábados.')
        ->and($professional->fresh()->active)->toBeFalse();

    $professional->update(['active' => true]);

    expect($professional->fresh()->active)->toBeTrue();
});

it('requires a name in the database', function () {
    expect(fn () => DB::table('professionals')->insert([
        'active' => true,
    ]))->toThrow(QueryException::class);
});
