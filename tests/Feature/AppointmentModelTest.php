<?php

use App\Enums\AppointmentStatus;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an appointment belongs to a client and defaults to scheduled', function () {
    $client = Client::factory()->create();
    $appointment = $client->appointments()->create(['notes' => 'Primeira visita']);

    expect($appointment->fresh()->client->is($client))->toBeTrue()
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->fresh()->notes)->toBe('Primeira visita')
        ->and($appointment->fresh()->cancelled_at)->toBeNull();
});

test('items retain service snapshots when the catalogue changes', function () {
    $client = Client::factory()->create();
    $professional = Professional::create(['name' => 'Ana']);
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $appointment = $client->appointments()->create();
    $item = $appointment->items()->create([
        'service_id' => $service->id,
        'professional_id' => $professional->id,
        'starts_at' => '2026-10-10 12:00:00',
        'ends_at' => '2026-10-10 13:00:00',
        'service_name' => 'Corte',
        'duration_minutes' => 60,
        'price_cents' => 8000,
    ]);

    $service->update(['name' => 'Corte premium', 'duration_minutes' => 90, 'price_cents' => 12000]);

    expect($item->fresh()->service->is($service))->toBeTrue()
        ->and($item->fresh()->professional->is($professional))->toBeTrue()
        ->and($item->fresh()->appointment->is($appointment))->toBeTrue()
        ->and($item->fresh()->service_name)->toBe('Corte')
        ->and($item->fresh()->duration_minutes)->toBe(60)
        ->and($item->fresh()->price_cents)->toBe(8000);
});

test('appointment bounds are derived from its earliest and latest items', function () {
    $client = Client::factory()->create();
    $professional = Professional::create(['name' => 'Ana']);
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $appointment = $client->appointments()->create();

    foreach ([['2026-10-10 14:00:00', '2026-10-10 15:00:00'], ['2026-10-10 12:00:00', '2026-10-10 13:00:00']] as [$start, $end]) {
        $appointment->items()->create([
            'service_id' => $service->id, 'professional_id' => $professional->id,
            'starts_at' => $start, 'ends_at' => $end,
            'service_name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000,
        ]);
    }

    expect($appointment->fresh()->starts_at->format('Y-m-d H:i:s'))->toBe('2026-10-10 12:00:00')
        ->and($appointment->fresh()->ends_at->format('Y-m-d H:i:s'))->toBe('2026-10-10 15:00:00');
});

test('appointments support all planned statuses and cancellation fields', function () {
    $appointment = Client::factory()->create()->appointments()->create();

    foreach (AppointmentStatus::cases() as $status) {
        $appointment->update(['status' => $status]);
        expect($appointment->fresh()->status)->toBe($status);
    }

    $appointment->update([
        'status' => AppointmentStatus::Cancelled,
        'cancelled_at' => '2026-10-10 12:00:00',
        'cancellation_reason' => 'Solicitação da cliente',
    ]);

    expect($appointment->fresh()->cancelled_at->format('Y-m-d H:i:s'))->toBe('2026-10-10 12:00:00')
        ->and($appointment->fresh()->cancellation_reason)->toBe('Solicitação da cliente');
});

test('deactivating catalogue records preserves appointment history', function () {
    $client = Client::factory()->create();
    $professional = Professional::create(['name' => 'Ana']);
    $service = Service::create(['name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000]);
    $appointment = $client->appointments()->create();
    $item = $appointment->items()->create([
        'service_id' => $service->id, 'professional_id' => $professional->id,
        'starts_at' => '2026-10-10 12:00:00', 'ends_at' => '2026-10-10 13:00:00',
        'service_name' => 'Corte', 'duration_minutes' => 60, 'price_cents' => 8000,
    ]);

    $client->delete();
    $professional->update(['active' => false]);
    $service->update(['active' => false]);

    expect($item->fresh()->service->name)->toBe('Corte')
        ->and($item->fresh()->professional->name)->toBe('Ana')
        ->and($item->fresh()->appointment->client->id)->toBe($client->id);
});
