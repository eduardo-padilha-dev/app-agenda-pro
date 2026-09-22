<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        $input = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $timezone = config('app.business_timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $date = isset($input['date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $input['date'], $timezone)
            : $today;
        $week = $date->startOfWeek(CarbonInterface::MONDAY);
        $periodStart = $week->utc();
        $periodEnd = $week->addWeek()->utc();
        $visibleStatuses = [
            AppointmentStatus::Scheduled,
            AppointmentStatus::Confirmed,
            AppointmentStatus::Completed,
        ];

        $appointments = Appointment::query()
            ->whereIn('status', $visibleStatuses)
            ->whereHas('items', fn ($query) => $query
                ->where('starts_at', '<', $periodEnd)
                ->where('ends_at', '>', $periodStart))
            ->with(['client', 'items.professional'])
            ->get();

        $visibleProfessionalIds = $appointments->flatMap(
            fn (Appointment $appointment) => $appointment->items->pluck('professional_id')
        )->unique();
        $professionals = Professional::query()
            ->with('availabilities')
            ->where(fn ($query) => $query->where('active', true)->orWhereKey($visibleProfessionalIds))
            ->orderBy('name')
            ->get();

        $blocks = ScheduleBlock::query()
            ->whereIn('professional_id', $professionals->modelKeys())
            ->where('starts_at', '<', $periodEnd)
            ->where('ends_at', '>', $periodStart)
            ->orderBy('starts_at')
            ->get();

        [$calendarStartMinutes, $calendarEndMinutes] = $this->calendarBounds(
            $appointments,
            $blocks,
            $professionals,
            $timezone,
            $periodStart,
            $periodEnd,
        );

        return view('calendar.index', [
            'appointments' => $this->appointmentsForView($appointments, $timezone),
            'professionals' => $this->professionalsForView($professionals),
            'clients' => $appointments->pluck('client')->unique('id')->values()->map(fn ($client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ]),
            'availabilities' => $this->availabilitiesForView($professionals),
            'blocks' => $blocks->map(fn (ScheduleBlock $block): array => [
                'professionalId' => $block->professional_id,
                'startTime' => $block->starts_at->setTimezone($timezone)->toIso8601String(),
                'endTime' => $block->ends_at->setTimezone($timezone)->toIso8601String(),
                'reason' => $block->reason,
            ]),
            'page' => 'agenda',
            'title' => 'Agenda',
            'date' => $date,
            'today' => $today,
            'week' => $week,
            'timezoneLabel' => 'GMT'.$date->format('P'),
            'calendarStartMinutes' => $calendarStartMinutes,
            'calendarEndMinutes' => $calendarEndMinutes,
            'calendarHeight' => ($calendarEndMinutes - $calendarStartMinutes) / 60 * 64,
            'statuses' => [
                'scheduled' => 'Agendado',
                'confirmed' => 'Confirmado',
                'completed' => 'Concluído',
                'cancelled' => 'Cancelado',
                'no_show' => 'Não compareceu',
            ],
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function calendarBounds(
        Collection $appointments,
        Collection $blocks,
        Collection $professionals,
        string $timezone,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $starts = collect([480]);
        $ends = collect([1260]);

        foreach ($appointments->flatMap->items as $item) {
            if ($item->starts_at->lessThan($periodEnd) && $item->ends_at->greaterThan($periodStart)) {
                $localStart = $item->starts_at->setTimezone($timezone);
                $localEnd = $item->ends_at->setTimezone($timezone);
                $starts->push($localStart->hour * 60 + $localStart->minute);
                $ends->push($localEnd->hour * 60 + $localEnd->minute);
            }
        }

        foreach ($blocks as $block) {
            $localStart = $block->starts_at->setTimezone($timezone);
            $localEnd = $block->ends_at->setTimezone($timezone);
            $endMinutes = $localEnd->hour * 60 + $localEnd->minute;
            $starts->push($localStart->hour * 60 + $localStart->minute);

            if ($localStart->isSameDay($localEnd)) {
                $ends->push($endMinutes);
            } else {
                $ends->push(1440);

                if ($endMinutes > 0) {
                    $starts->push(0);
                    $ends->push($endMinutes);
                }
            }
        }

        foreach ($professionals->flatMap->availabilities as $availability) {
            [$startHour, $startMinute] = array_map('intval', explode(':', $availability->starts_at));
            [$endHour, $endMinute] = array_map('intval', explode(':', $availability->ends_at));
            $starts->push($startHour * 60 + $startMinute);
            $ends->push($endHour * 60 + $endMinute);
        }

        $calendarStart = max(0, intdiv((int) $starts->min(), 60) * 60);
        $calendarEnd = min(1440, (int) ceil(((int) $ends->max()) / 60) * 60);

        return [$calendarStart, max($calendarStart + 60, $calendarEnd)];
    }

    private function appointmentsForView(Collection $appointments, string $timezone): Collection
    {
        return $appointments->map(fn (Appointment $appointment): array => [
            'id' => $appointment->id,
            'clientId' => $appointment->client_id,
            'status' => $appointment->status->value,
            'notes' => $appointment->notes,
            'cancellationReason' => $appointment->cancellation_reason,
            'items' => $appointment->items->map(fn ($item): array => [
                'id' => $item->id,
                'professionalId' => $item->professional_id,
                'startTime' => $item->starts_at->setTimezone($timezone)->toIso8601String(),
                'endTime' => $item->ends_at->setTimezone($timezone)->toIso8601String(),
                'serviceNameSnapshot' => $item->service_name,
                'durationSnapshot' => $item->duration_minutes,
                'priceSnapshot' => $item->price_cents / 100,
            ])->all(),
        ]);
    }

    private function professionalsForView(Collection $professionals): Collection
    {
        $colors = ['#0d9488', '#7c3aed', '#db2777', '#d97706', '#2563eb', '#16a34a', '#dc2626', '#475569'];

        return $professionals->map(fn (Professional $professional): array => [
            'id' => $professional->id,
            'name' => $professional->name,
            'active' => $professional->active,
            'color' => $colors[($professional->id - 1) % count($colors)],
        ]);
    }

    private function availabilitiesForView(Collection $professionals): Collection
    {
        return $professionals->flatMap(fn (Professional $professional) => $professional->availabilities
            ->groupBy('weekday')
            ->map(fn (Collection $intervals, int $weekday): array => [
                'professionalId' => $professional->id,
                'dayOfWeek' => $weekday,
                'intervals' => $intervals->map(fn ($interval): array => [
                    'start' => $interval->starts_at,
                    'end' => $interval->ends_at,
                ])->all(),
            ]))->values();
    }
}
