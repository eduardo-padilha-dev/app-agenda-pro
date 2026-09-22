<?php

namespace App\Domain\Scheduling;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentItem;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AppointmentConflictChecker
{
    /**
     * Checks candidates inside the transaction that holds the client and professional locks.
     *
     * @param  array<int, array{service_id: int, professional_id: int, starts_at: \DateTimeInterface, ends_at: \DateTimeInterface}>  $items
     */
    public function assertAvailable(Client $client, array $items, ?int $ignoringAppointmentId = null): void
    {
        if ($client->trashed()) {
            $this->fail('client_id', 'A cliente está inativa.');
        }

        if ($items === []) {
            $this->fail('items', 'Adicione pelo menos um serviço ao agendamento.');
        }

        if ($ignoringAppointmentId !== null && ! Appointment::query()
            ->whereKey($ignoringAppointmentId)
            ->where('client_id', $client->id)
            ->exists()) {
            $this->fail('appointment_id', 'O agendamento não pertence a esta cliente.');
        }

        $accepted = [];
        $blockingStatuses = [
            AppointmentStatus::Scheduled->value,
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::Completed->value,
        ];

        foreach ($items as $index => $item) {
            $service = Service::find($item['service_id']);
            if (! $service?->active) {
                $this->fail("items.$index.service_id", 'O serviço está inativo ou não existe.');
            }

            $professional = Professional::find($item['professional_id']);
            if (! $professional?->active) {
                $this->fail("items.$index.professional_id", 'O profissional está inativo ou não existe.');
            }

            if (! $professional->services()->whereKey($service->id)->exists()) {
                $this->fail("items.$index.service_id", 'O profissional não realiza este serviço.');
            }

            $start = CarbonImmutable::instance($item['starts_at'])->utc();
            $end = CarbonImmutable::instance($item['ends_at'])->utc();
            $startUtc = $start->format('Y-m-d H:i:s');
            $endUtc = $end->format('Y-m-d H:i:s');

            if ($end->getTimestamp() <= $start->getTimestamp()) {
                $this->fail("items.$index.ends_at", 'O término deve ser posterior ao início.');
            }

            if ($end->getTimestamp() - $start->getTimestamp() !== $service->duration_minutes * 60) {
                $this->fail("items.$index.ends_at", 'O intervalo deve ter a duração definida para o serviço.');
            }

            $localStart = $start->setTimezone(config('app.business_timezone'));
            $localEnd = $end->setTimezone(config('app.business_timezone'));
            $available = $localStart->toDateString() === $localEnd->toDateString()
                && $professional->availabilities()
                    ->where('weekday', $localStart->dayOfWeek)
                    ->get()
                    ->contains(fn ($availability) => substr($availability->starts_at, 0, 8) <= $localStart->format('H:i:s')
                        && substr($availability->ends_at, 0, 8) >= $localEnd->format('H:i:s'));

            if (! $available) {
                $this->fail("items.$index.starts_at", 'O horário está fora da disponibilidade do profissional.');
            }

            if ($professional->blocks()->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->exists()) {
                $this->fail("items.$index.starts_at", 'O horário cruza um bloqueio do profissional.');
            }

            $overlappingItems = AppointmentItem::query()
                ->where('starts_at', '<', $endUtc)
                ->where('ends_at', '>', $startUtc)
                ->whereHas('appointment', fn ($query) => $query
                    ->whereIn('status', $blockingStatuses)
                    ->when($ignoringAppointmentId !== null, fn ($query) => $query->whereKeyNot($ignoringAppointmentId)));

            if ((clone $overlappingItems)->where('professional_id', $professional->id)->exists()) {
                $this->fail("items.$index.professional_id", 'O profissional já possui um atendimento neste horário.');
            }

            if ((clone $overlappingItems)->whereHas('appointment', fn ($query) => $query->where('client_id', $client->id))->exists()) {
                $this->fail("items.$index.starts_at", 'A cliente já possui um atendimento neste horário.');
            }

            foreach ($accepted as [$previousStart, $previousEnd]) {
                if ($start->lessThan($previousEnd) && $end->greaterThan($previousStart)) {
                    $this->fail("items.$index.starts_at", 'Os itens deste agendamento não podem se sobrepor.');
                }
            }

            $accepted[] = [$start, $end];
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
