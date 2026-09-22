<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeAppointmentStatus
{
    public function handle(Appointment $appointment, AppointmentStatus $target, ?string $cancellationReason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $target, $cancellationReason) {
            Client::withTrashed()->whereKey($appointment->client_id)->lockForUpdate()->firstOrFail();
            $lockedAppointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            $professionalIds = $lockedAppointment->items()->pluck('professional_id')
                ->map(fn ($id) => (int) $id)->unique()->sort(SORT_NUMERIC)->values();
            foreach ($professionalIds as $professionalId) {
                Professional::query()->whereKey($professionalId)->lockForUpdate()->firstOrFail();
            }

            $allowed = match ($lockedAppointment->status) {
                AppointmentStatus::Scheduled => [
                    AppointmentStatus::Confirmed,
                    AppointmentStatus::Completed,
                    AppointmentStatus::Cancelled,
                    AppointmentStatus::NoShow,
                ],
                AppointmentStatus::Confirmed => [
                    AppointmentStatus::Completed,
                    AppointmentStatus::Cancelled,
                    AppointmentStatus::NoShow,
                ],
                default => [],
            };
            if (! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'Esta alteração de status não é permitida.']);
            }

            $changes = ['status' => $target];
            if ($target === AppointmentStatus::Cancelled) {
                $changes['cancelled_at'] = CarbonImmutable::now('UTC');
                $changes['cancellation_reason'] = $cancellationReason;
            }

            $lockedAppointment->update($changes);

            return $lockedAppointment;
        });
    }
}
