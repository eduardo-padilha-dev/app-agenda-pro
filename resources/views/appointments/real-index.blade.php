@extends('layouts.app')

@section('content')
<div class="flex h-full min-h-0 flex-col bg-slate-50">
    <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
        <div class="flex items-center justify-between gap-3"><h1 class="font-display text-lg font-semibold">Agendamentos</h1><a href="{{ route('appointments.create') }}" class="primary-button"><x-icon name="plus" size="15" />Novo agendamento</a></div>
        @if(session('success'))<div class="mt-3"><x-flash /></div>@endif
        @if($errors->any())<p class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>@endif
    </header>
    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3 md:px-6">
        @forelse($appointments as $appointment)
            <article class="rounded-xl border border-slate-200 bg-white p-3.5">
                <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-slate-800">{{ $appointment->client->name }}</p><p class="text-xs text-slate-500">{{ $appointment->starts_at?->setTimezone(config('app.business_timezone'))->format('d/m/Y H:i') }} – {{ $appointment->ends_at?->setTimezone(config('app.business_timezone'))->format('H:i') }}</p></div><x-status :status="$appointment->status->value" /></div>
                <div class="mt-2 space-y-1">
                    @foreach($appointment->items as $item)
                        <p class="text-xs text-slate-600">{{ $item->service_name }} · {{ $item->professional->name }} · {{ $item->starts_at->setTimezone(config('app.business_timezone'))->format('d/m H:i') }}</p>
                    @endforeach
                </div>
                <p class="mt-2 border-t border-slate-100 pt-2 text-xs text-slate-600">{{ $appointment->items->count() }} item(s) · R$ {{ number_format($appointment->items->sum('price_cents') / 100, 2, ',', '.') }}</p>
                @if(in_array($appointment->status, [\App\Enums\AppointmentStatus::Scheduled, \App\Enums\AppointmentStatus::Confirmed], true))
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                        <a href="{{ route('appointments.edit', $appointment) }}" class="small-button text-teal-700">Reagendar</a>
                        @if($appointment->status === \App\Enums\AppointmentStatus::Scheduled)
                            <form action="{{ route('appointments.confirm', $appointment) }}" method="POST">@csrf<button type="submit" class="small-button text-blue-700">Confirmar</button></form>
                        @endif
                        <button type="button" data-open="complete-appointment-{{ $appointment->id }}" class="small-button text-green-700">Concluir</button>
                        <button type="button" data-open="no-show-appointment-{{ $appointment->id }}" class="small-button text-orange-700">Não compareceu</button>
                        <button type="button" data-open="cancel-appointment-{{ $appointment->id }}" class="small-button text-red-600">Cancelar</button>

                        <x-dialog :id="'complete-appointment-'.$appointment->id" title="Concluir atendimento">
                            <form action="{{ route('appointments.complete', $appointment) }}" method="POST" class="space-y-4 p-6">
                                @csrf
                                <p class="text-sm text-slate-600">Deseja concluir o atendimento de <strong>{{ $appointment->client->name }}</strong>?</p>
                                <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1 bg-green-600 hover:bg-green-700">Concluir</button></div>
                            </form>
                        </x-dialog>

                        <x-dialog :id="'no-show-appointment-'.$appointment->id" title="Registrar não comparecimento">
                            <form action="{{ route('appointments.no-show', $appointment) }}" method="POST" class="space-y-4 p-6">
                                @csrf
                                <p class="text-sm text-slate-600">Deseja registrar que <strong>{{ $appointment->client->name }}</strong> não compareceu?</p>
                                <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="secondary-button flex-1 border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100">Confirmar</button></div>
                            </form>
                        </x-dialog>

                        <x-dialog :id="'cancel-appointment-'.$appointment->id" title="Cancelar agendamento">
                            <form action="{{ route('appointments.cancel', $appointment) }}" method="POST" class="space-y-4 p-6">
                                @csrf
                                <p class="text-sm text-slate-600">Deseja cancelar o agendamento de <strong>{{ $appointment->client->name }}</strong>?</p>
                                <label class="block text-sm text-slate-600">Motivo do cancelamento (opcional)<textarea name="cancellation_reason" rows="2" class="input-base mt-2"></textarea></label>
                                <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Voltar</button><button type="submit" class="danger-button flex-1">Cancelar agendamento</button></div>
                            </form>
                        </x-dialog>
                    </div>
                @endif
            </article>
        @empty
            <p class="py-16 text-center text-sm text-slate-500">Nenhum agendamento cadastrado.</p>
        @endforelse
    </div>
    @if($appointments->hasPages())<footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 md:px-6">{{ $appointments->links() }}</footer>@endif
</div>
@endsection
