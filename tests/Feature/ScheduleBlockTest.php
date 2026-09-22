<?php

use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('block routes require authentication', function () {
    $professional = Professional::create(['name' => 'Ana']);
    $block = $professional->blocks()->create([
        'starts_at' => '2026-10-10 12:00:00', 'ends_at' => '2026-10-10 13:00:00',
    ]);

    $this->post(route('professionals.blocks.store', $professional), [
        'starts_at' => '2026-10-11T09:00', 'ends_at' => '2026-10-11T10:00',
    ])->assertRedirect(route('login'));
    $this->delete(route('professionals.blocks.destroy', [$professional, $block]))->assertRedirect(route('login'));

    $this->assertDatabaseCount('schedule_blocks', 1);
});

test('a block stores UTC instants and an optional reason', function () {
    $professional = Professional::create(['name' => 'Ana']);

    $this->actingAs(User::factory()->create())->post(route('professionals.blocks.store', $professional), [
        'starts_at' => '2026-10-10T09:00',
        'ends_at' => '2026-10-10T10:30',
        'reason' => 'Consulta médica',
    ])->assertRedirect(route('professionals.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('schedule_blocks', [
        'professional_id' => $professional->id,
        'starts_at' => '2026-10-10 12:00:00',
        'ends_at' => '2026-10-10 13:30:00',
        'reason' => 'Consulta médica',
    ]);

    $this->get(route('professionals.index'))->assertOk()->assertSee('Consulta médica')->assertSee('09:00');
});

test('a block can span multiple days without a reason', function () {
    $professional = Professional::create(['name' => 'Ana']);

    $this->actingAs(User::factory()->create())->post(route('professionals.blocks.store', $professional), [
        'starts_at' => '2026-10-10T09:00', 'ends_at' => '2026-10-12T18:00', 'reason' => '',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('schedule_blocks', [
        'professional_id' => $professional->id, 'starts_at' => '2026-10-10 12:00:00',
        'ends_at' => '2026-10-12 21:00:00', 'reason' => null,
    ]);
});

test('invalid block periods do not persist', function (array $changes, string $field) {
    $professional = Professional::create(['name' => 'Ana']);
    $data = array_merge([
        'starts_at' => '2026-10-10T09:00', 'ends_at' => '2026-10-10T10:00',
    ], $changes);

    $this->actingAs(User::factory()->create())->from(route('professionals.index'))
        ->post(route('professionals.blocks.store', $professional), $data)
        ->assertRedirect(route('professionals.index'))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('schedule_blocks', 0);
})->with([
    'missing start' => [['starts_at' => ''], 'starts_at'],
    'invalid end' => [['ends_at' => 'invalid'], 'ends_at'],
    'equal instants' => [['ends_at' => '2026-10-10T09:00'], 'ends_at'],
    'reversed instants' => [['ends_at' => '2026-10-10T08:00'], 'ends_at'],
    'invalid reason' => [['reason' => ['array']], 'reason'],
]);

test('blocks cannot be added to inactive professionals', function () {
    $professional = Professional::create(['name' => 'Ana', 'active' => false]);

    $this->actingAs(User::factory()->create())->post(route('professionals.blocks.store', $professional), [
        'starts_at' => '2026-10-10T09:00', 'ends_at' => '2026-10-10T10:00',
    ])->assertNotFound();

    $this->assertDatabaseCount('schedule_blocks', 0);
});

test('a block can be removed only through its own professional', function () {
    $one = Professional::create(['name' => 'Ana']);
    $two = Professional::create(['name' => 'Bia']);
    $block = $one->blocks()->create([
        'starts_at' => '2026-10-10 12:00:00', 'ends_at' => '2026-10-10 13:00:00',
    ]);

    $this->actingAs(User::factory()->create())
        ->delete(route('professionals.blocks.destroy', [$two, $block]))->assertNotFound();
    $this->assertDatabaseCount('schedule_blocks', 1);

    $this->delete(route('professionals.blocks.destroy', [$one, $block]))
        ->assertRedirect(route('professionals.index'));
    $this->assertDatabaseCount('schedule_blocks', 0);
});
