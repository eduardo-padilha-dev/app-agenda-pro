<?php

namespace App\Http\Controllers;

use App\Actions\Scheduling\CreateAppointment;
use App\Actions\Scheduling\RescheduleAppointment;
use App\Enums\AppointmentStatus;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        return view('appointments.real-index', [
            'appointments' => Appointment::query()->with(['client', 'items.professional'])
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
            'title' => 'Agendamentos',
            'page' => 'agendamentos',
        ]);
    }

    public function create(Request $request): View
    {
        $input = $request->validate(['starts_at' => ['nullable', 'date_format:Y-m-d\TH:i']]);

        return view('appointments.create', [
            'clients' => Client::query()->orderBy('name')->get(),
            'services' => Service::query()->where('active', true)->with('professionals')->orderBy('name')->get(),
            'professionals' => Professional::query()->where('active', true)->orderBy('name')->get(),
            'initialStart' => $input['starts_at'] ?? '',
            'title' => 'Novo agendamento',
            'page' => 'agendamentos',
        ]);
    }

    public function store(StoreAppointmentRequest $request, CreateAppointment $createAppointment): RedirectResponse
    {
        $createAppointment->handle($request->validated());

        return redirect()->route('appointments.index')->with('success', 'Agendamento criado com sucesso!');
    }

    public function edit(Appointment $appointment): View
    {
        if (! in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
            throw ValidationException::withMessages(['status' => 'Somente agendamentos agendados ou confirmados podem ser reagendados.']);
        }

        $appointment->load(['client', 'items']);
        $serviceIds = $appointment->items->pluck('service_id');
        $professionalIds = $appointment->items->pluck('professional_id');

        return view('appointments.edit', [
            'appointment' => $appointment,
            'services' => Service::query()->where(fn ($query) => $query->where('active', true)->orWhereKey($serviceIds))->orderBy('name')->get(),
            'professionals' => Professional::query()->where(fn ($query) => $query->where('active', true)->orWhereKey($professionalIds))->orderBy('name')->get(),
            'title' => 'Reagendar atendimento',
            'page' => 'agendamentos',
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, RescheduleAppointment $rescheduleAppointment): RedirectResponse
    {
        $rescheduleAppointment->handle($appointment, $request->validated());

        return redirect()->route('appointments.index')->with('success', 'Agendamento reagendado com sucesso!');
    }
}
