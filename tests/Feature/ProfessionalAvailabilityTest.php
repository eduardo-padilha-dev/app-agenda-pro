<?php

use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('availability requires authentication', function () {
    $professional = Professional::create(['name' => 'Ana']);

    $this->put(route('professionals.availability.update', $professional), [
        'intervals' => [1 => [['starts_at' => '09:00', 'ends_at' => '18:00']]],
    ])->assertRedirect(route('login'));

    $this->assertDatabaseCount('professional_availabilities', 0);
});

test('a professional can have multiple local intervals per day and adjacent intervals', function () {
    $professional = Professional::create(['name' => 'Ana']);

    $this->actingAs(User::factory()->create())->put(route('professionals.availability.update', $professional), [
        'intervals' => [
            1 => [
                ['starts_at' => '13:00', 'ends_at' => '18:00'],
                ['starts_at' => '09:00', 'ends_at' => '12:00'],
            ],
            2 => [['starts_at' => '09:00', 'ends_at' => '12:00'], ['starts_at' => '12:00', 'ends_at' => '17:00']],
        ],
    ])->assertRedirect(route('professionals.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('professional_availabilities', 4);
    $this->assertDatabaseHas('professional_availabilities', [
        'professional_id' => $professional->id, 'weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00',
    ]);
    expect($professional->availabilities()->where('weekday', 2)->count())->toBe(2);
});

test('saving availability replaces previous intervals and can clear the week', function () {
    $professional = Professional::create(['name' => 'Ana']);
    $professional->availabilities()->createMany([
        ['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00'],
        ['weekday' => 2, 'starts_at' => '09:00', 'ends_at' => '12:00'],
    ]);

    $this->actingAs(User::factory()->create())->put(route('professionals.availability.update', $professional), [
        'intervals' => [1 => [['starts_at' => '10:00', 'ends_at' => '16:00']]],
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('professional_availabilities', 1);
    $this->assertDatabaseHas('professional_availabilities', ['professional_id' => $professional->id, 'weekday' => 1, 'starts_at' => '10:00']);

    $this->put(route('professionals.availability.update', $professional), [])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('professional_availabilities', 0);
});

test('invalid intervals leave the existing schedule untouched', function (array $intervals) {
    $professional = Professional::create(['name' => 'Ana']);
    $professional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']);

    $this->actingAs(User::factory()->create())->from(route('professionals.index'))
        ->put(route('professionals.availability.update', $professional), ['intervals' => $intervals])
        ->assertRedirect(route('professionals.index'))->assertSessionHasErrors();

    $this->assertDatabaseCount('professional_availabilities', 1);
    $this->assertDatabaseHas('professional_availabilities', ['professional_id' => $professional->id, 'weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']);
})->with([
    'overlap' => [[1 => [['starts_at' => '09:00', 'ends_at' => '12:00'], ['starts_at' => '11:00', 'ends_at' => '14:00']]]],
    'reversed' => [[1 => [['starts_at' => '18:00', 'ends_at' => '09:00']]]],
    'equal' => [[1 => [['starts_at' => '09:00', 'ends_at' => '09:00']]]],
    'invalid time' => [[1 => [['starts_at' => '25:00', 'ends_at' => '26:00']]]],
    'invalid day' => [[7 => [['starts_at' => '09:00', 'ends_at' => '12:00']]]],
]);

test('inactive professionals cannot change availability', function () {
    $professional = Professional::create(['name' => 'Ana', 'active' => false]);

    $this->actingAs(User::factory()->create())->put(route('professionals.availability.update', $professional), [
        'intervals' => [1 => [['starts_at' => '09:00', 'ends_at' => '12:00']]],
    ])->assertNotFound();

    $this->assertDatabaseCount('professional_availabilities', 0);
});

test('the professional list shows saved availability controls', function () {
    $professional = Professional::create(['name' => 'Ana']);
    $professional->availabilities()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']);

    $this->actingAs(User::factory()->create())->get(route('professionals.index'))
        ->assertOk()->assertSee('Disponibilidade')->assertSee('Segunda-feira')->assertSee('09:00');
});
