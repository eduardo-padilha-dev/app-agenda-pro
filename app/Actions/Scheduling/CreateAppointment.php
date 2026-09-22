<?php

namespace App\Actions\Scheduling;

use App\Domain\Scheduling\AppointmentConflictChecker;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAppointment
{
    public function __construct(private AppointmentConflictChecker $conflicts) {}

    /** @param array{client_id: int, notes?: ?string, items: array<int|string, array{service_id: int, professional_id: int, starts_at: string}>} $data */
    public function handle(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $client = Client::withTrashed()->lockForUpdate()->find($data['client_id']);
            if (! $client || $client->trashed()) {
                throw ValidationException::withMessages(['client_id' => 'Selecione uma cliente ativa.']);
            }

            $professionalIds = collect($data['items'])->pluck('professional_id')->map(fn ($id) => (int) $id)->unique()->sort(SORT_NUMERIC)->values();
            foreach ($professionalIds as $professionalId) {
                Professional::query()->whereKey($professionalId)->lockForUpdate()->first();
            }

            $candidates = [];
            $services = [];
            foreach ($data['items'] as $index => $item) {
                $service = Service::find($item['service_id']);
                if (! $service) {
                    throw ValidationException::withMessages(["items.$index.service_id" => 'Selecione um serviço válido.']);
                }

                $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $item['starts_at'], config('app.business_timezone'))->utc();
                $candidates[$index] = [
                    'service_id' => $service->id,
                    'professional_id' => $item['professional_id'],
                    'starts_at' => $start,
                    'ends_at' => $start->addMinutes($service->duration_minutes),
                ];
                $services[$index] = $service;
            }

            $this->conflicts->assertAvailable($client, $candidates);

            $appointment = $client->appointments()->create(['notes' => $data['notes'] ?? null]);
            foreach ($candidates as $index => $candidate) {
                $service = $services[$index];
                $appointment->items()->create([
                    'service_id' => $service->id,
                    'professional_id' => $candidate['professional_id'],
                    'starts_at' => $candidate['starts_at']->format('Y-m-d H:i:s'),
                    'ends_at' => $candidate['ends_at']->format('Y-m-d H:i:s'),
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price_cents' => $service->price_cents,
                ]);
            }

            return $appointment;
        });
    }
}
