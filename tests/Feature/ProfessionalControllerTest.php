<?php

use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('professional routes require authentication', function () {
    $professional = Professional::create(['name' => 'Ana']);

    $this->get(route('professionals.index'))->assertRedirect(route('login'));
    $this->post(route('professionals.store'), ['name' => 'Bia'])->assertRedirect(route('login'));
    $this->patch(route('professionals.update', $professional), ['name' => 'Bia'])->assertRedirect(route('login'));
    $this->delete(route('professionals.destroy', $professional))->assertRedirect(route('login'));
    $this->patch(route('professionals.restore', $professional))->assertRedirect(route('login'));

    $this->assertDatabaseCount('professionals', 1);
    $this->assertDatabaseHas('professionals', ['id' => $professional->id, 'name' => 'Ana', 'active' => true]);
});

test('authenticated users can create and update professionals', function () {
    $this->actingAs(User::factory()->create())->post(route('professionals.store'), [
        'name' => 'Ana Rodrigues', 'phone' => '11999990000', 'email' => 'ana@example.test', 'notes' => 'Manhã',
    ])->assertRedirect(route('professionals.index'))->assertSessionHasNoErrors();

    $professional = Professional::firstOrFail();
    expect($professional->active)->toBeTrue();

    $this->patch(route('professionals.update', $professional), [
        'name' => 'Ana Costa', 'phone' => '', 'email' => '', 'notes' => '',
    ])->assertRedirect(route('professionals.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('professionals', [
        'id' => $professional->id, 'name' => 'Ana Costa', 'phone' => null, 'email' => null, 'notes' => null, 'active' => true,
    ]);
});

test('invalid professional details do not change the database', function (string $field, mixed $value) {
    $professional = Professional::create(['name' => 'Original']);
    $data = ['name' => 'Ana', 'phone' => '11999990000', 'email' => 'ana@example.test', 'notes' => 'Nota'];
    $data[$field] = $value;

    $this->actingAs(User::factory()->create())->from(route('professionals.index'))
        ->patch(route('professionals.update', $professional), $data)
        ->assertRedirect(route('professionals.index'))->assertSessionHasErrors($field);

    $this->assertDatabaseHas('professionals', ['id' => $professional->id, 'name' => 'Original']);
})->with([
    'blank name' => ['name', ''],
    'numeric name' => ['name', '123'],
    'long name' => ['name', str_repeat('a', 256)],
    'invalid phone' => ['phone', 'abc'],
    'invalid email' => ['email', 'invalid'],
    'invalid notes' => ['notes', ['bad']],
]);

test('invalid creation does not persist a professional and keeps form input', function () {
    $this->actingAs(User::factory()->create())->from(route('professionals.index'))
        ->post(route('professionals.store'), ['name' => 'Ana', 'phone' => '123'])
        ->assertRedirect(route('professionals.index'))
        ->assertSessionHasErrors('phone')
        ->assertSessionHasInput('name', 'Ana');

    $this->assertDatabaseCount('professionals', 0);
});

test('professionals can be deactivated and restored without losing data', function () {
    $professional = Professional::create(['name' => 'Ana', 'notes' => 'Histórico']);
    $this->actingAs(User::factory()->create())->delete(route('professionals.destroy', $professional))
        ->assertRedirect(route('professionals.index'));

    $this->assertDatabaseHas('professionals', ['id' => $professional->id, 'active' => false, 'notes' => 'Histórico']);
    $this->get(route('professionals.index'))->assertDontSee('Histórico');
    $this->get(route('professionals.index', ['show_inactive' => 1]))->assertSee('Ana')->assertSee('Reativar');

    $this->patch(route('professionals.restore', $professional))->assertRedirect(route('professionals.index'));
    $this->assertDatabaseHas('professionals', ['id' => $professional->id, 'active' => true, 'notes' => 'Histórico']);
    $this->patch(route('professionals.restore', $professional))->assertNotFound();
});

test('inactive professionals cannot be edited or deactivated again', function () {
    $professional = Professional::create(['name' => 'Ana', 'active' => false]);

    $this->actingAs(User::factory()->create())
        ->patch(route('professionals.update', $professional), ['name' => 'Alterado'])
        ->assertNotFound();
    $this->delete(route('professionals.destroy', $professional))->assertNotFound();

    $this->assertDatabaseHas('professionals', ['id' => $professional->id, 'name' => 'Ana', 'active' => false]);
});

test('professional list supports search and pagination', function () {
    foreach (range(1, 11) as $number) {
        Professional::create(['name' => sprintf('Ana %02d', $number)]);
    }
    Professional::create(['name' => 'Zelia', 'phone' => '(11) 98888-7777']);

    $this->actingAs(User::factory()->create())->get(route('professionals.index'))
        ->assertOk()->assertSee('Ana 01')->assertDontSee('Zelia');
    $this->get(route('professionals.index', ['page' => 2]))->assertSee('Zelia');
    $this->get(route('professionals.index', ['search' => '88887777']))
        ->assertOk()->assertSee('Zelia')->assertDontSee('Ana 01');
});
