<?php

namespace App\Http\Controllers;

use App\Actions\Scheduling\ChangeAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentStatusController extends Controller
{
    public function confirm(Appointment $appointment, ChangeAppointmentStatus $changeStatus): RedirectResponse
    {
        $changeStatus->handle($appointment, AppointmentStatus::Confirmed);

        return redirect()->route('appointments.index')->with('success', 'Agendamento confirmado!');
    }

    public function complete(Appointment $appointment, ChangeAppointmentStatus $changeStatus): RedirectResponse
    {
        $changeStatus->handle($appointment, AppointmentStatus::Completed);

        return redirect()->route('appointments.index')->with('success', 'Agendamento concluído!');
    }

    public function cancel(Request $request, Appointment $appointment, ChangeAppointmentStatus $changeStatus): RedirectResponse
    {
        $data = $request->validate(['cancellation_reason' => ['nullable', 'string']]);
        $changeStatus->handle($appointment, AppointmentStatus::Cancelled, $data['cancellation_reason'] ?? null);

        return redirect()->route('appointments.index')->with('success', 'Agendamento cancelado!');
    }

    public function noShow(Appointment $appointment, ChangeAppointmentStatus $changeStatus): RedirectResponse
    {
        $changeStatus->handle($appointment, AppointmentStatus::NoShow);

        return redirect()->route('appointments.index')->with('success', 'Ausência registrada!');
    }
}
