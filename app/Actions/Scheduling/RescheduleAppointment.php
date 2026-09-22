<?php

namespace App\Actions\Scheduling;

use App\Domain\Scheduling\AppointmentConflictChecker;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleAppointment
{
    public function __construct(private AppointmentConflictChecker $conflicts) {}

    /** @param array{notes?: ?string, items: array<int|string, array{service_id: int, professional_id: int, starts_at: string}>} $data */
    public function handle(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $client = Client::withTrashed()->whereKey($appointment->client_id)->lockForUpdate()->firstOrFail();
            $lockedAppointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedAppointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['status' => 'Somente agendamentos agendados ou confirmados podem ser reagendados.']);
            }

            $professionalIds = $lockedAppointment->items()->pluck('professional_id')
                ->merge(collect($data['items'])->pluck('professional_id'))
                ->map(fn ($id) => (int) $id)->unique()->sort(SORT_NUMERIC)->values();
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

            $this->conflicts->assertAvailable($client, $candidates, $lockedAppointment->id);

            $lockedAppointment->items()->delete();
            foreach ($candidates as $index => $candidate) {
                $service = $services[$index];
                $lockedAppointment->items()->create([
                    'service_id' => $service->id,
                    'professional_id' => $candidate['professional_id'],
                    'starts_at' => $candidate['starts_at']->format('Y-m-d H:i:s'),
                    'ends_at' => $candidate['ends_at']->format('Y-m-d H:i:s'),
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price_cents' => $service->price_cents,
                ]);
            }

            $lockedAppointment->update([
                'notes' => $data['notes'] ?? null,
                'status' => AppointmentStatus::Scheduled,
            ]);

            return $lockedAppointment;
        });
    }
}
