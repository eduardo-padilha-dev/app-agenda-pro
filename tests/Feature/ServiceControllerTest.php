<?php

use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('service routes require authentication', function () {
    $this->get('/services')->assertRedirect(route('login'));
    $this->post('/services', [])->assertRedirect(route('login'));
});

test('a service stores cents and its professional associations', function () {
    $one = Professional::create(['name' => 'Ana']);
    $two = Professional::create(['name' => 'Bia']);

    $this->actingAs(User::factory()->create())->post(route('services.store'), [
        'name' => 'Maquiagem', 'description' => 'Social', 'duration_minutes' => 90,
        'price' => '120.50', 'professional_ids' => [$one->id, $two->id],
    ])->assertRedirect(route('services.index'))->assertSessionHasNoErrors();

    $service = Service::firstOrFail();
    expect($service->price_cents)->toBe(12050)
        ->and($service->active)->toBeTrue()
        ->and($service->professionals()->pluck('professionals.id')->all())->toEqualCanonicalizing([$one->id, $two->id]);
});

test('editing a service updates its associations and clears optional fields', function () {
    $one = Professional::create(['name' => 'Ana']);
    $two = Professional::create(['name' => 'Bia']);
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000, 'description' => 'Antiga']);
    $service->professionals()->attach($one);

    $this->actingAs(User::factory()->create())->patch(route('services.update', $service), [
        'name' => 'Corte simples', 'description' => '', 'duration_minutes' => 45,
        'price' => '75.00', 'professional_ids' => [$two->id],
    ])->assertRedirect(route('services.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Corte simples', 'description' => null, 'duration_minutes' => 45, 'price_cents' => 7500]);
    expect($service->professionals()->pluck('professionals.id')->all())->toBe([$two->id]);

    $this->patch(route('services.update', $service), [
        'name' => 'Corte simples', 'duration_minutes' => 45, 'price' => '75',
    ])->assertSessionHasNoErrors();
    expect($service->professionals()->count())->toBe(0);
});

test('invalid service input does not persist changes', function (array $changes, string $field) {
    $service = Service::create(['name' => 'Original', 'duration_minutes' => 60, 'price_cents' => 10000]);
    $data = array_merge(['name' => 'Alterado', 'duration_minutes' => 30, 'price' => '50.00'], $changes);

    $this->actingAs(User::factory()->create())->from(route('services.index'))
        ->patch(route('services.update', $service), $data)
        ->assertRedirect(route('services.index'))->assertSessionHasErrors($field);

    $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Original', 'duration_minutes' => 60, 'price_cents' => 10000]);
})->with([
    'name' => [['name' => ''], 'name'],
    'duration' => [['duration_minutes' => 0], 'duration_minutes'],
    'price negative' => [['price' => '-1'], 'price'],
    'price precision' => [['price' => '10.999'], 'price'],
    'missing professional' => [['professional_ids' => [999]], 'professional_ids.0'],
]);

test('inactive professionals cannot be assigned to a service', function () {
    $professional = Professional::create(['name' => 'Ana', 'active' => false]);

    $this->actingAs(User::factory()->create())->post(route('services.store'), [
        'name' => 'Corte', 'duration_minutes' => 60, 'price' => '50', 'professional_ids' => [$professional->id],
    ])->assertSessionHasErrors('professional_ids.0');

    $this->assertDatabaseCount('services', 0);
});

test('services can be deactivated and restored while preserving associations', function () {
    $professional = Professional::create(['name' => 'Ana']);
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $service->professionals()->attach($professional);

    $this->actingAs(User::factory()->create())->delete(route('services.destroy', $service))
        ->assertRedirect(route('services.index'));
    $this->get(route('services.index'))->assertDontSee('Corte');
    $this->get(route('services.index', ['show_inactive' => 1]))->assertSee('Corte')->assertSee('Reativar');
    expect($service->fresh()->active)->toBeFalse()
        ->and($service->professionals()->count())->toBe(1);

    $this->patch(route('services.restore', $service))->assertRedirect(route('services.index'));
    expect($service->fresh()->active)->toBeTrue();
});

test('services list supports search and pagination', function () {
    foreach (range(1, 11) as $number) {
        Service::create(['name' => sprintf('Corte %02d', $number), 'duration_minutes' => 30, 'price_cents' => 1000]);
    }
    Service::create(['name' => 'Maquiagem', 'duration_minutes' => 90, 'price_cents' => 12000]);

    $this->actingAs(User::factory()->create())->get(route('services.index'))->assertOk()->assertDontSee('Maquiagem');
    $this->get(route('services.index', ['page' => 2]))->assertSee('Maquiagem');
    $this->get(route('services.index', ['search' => 'maquia']))->assertSee('Maquiagem')->assertDontSee('Corte 01');
});
