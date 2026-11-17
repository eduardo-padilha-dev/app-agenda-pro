@foreach($appointments as $appointment)
@php
    $client = collect($clients)->firstWhere('id', $appointment['clientId']);
    $start = \Carbon\CarbonImmutable::parse(min(array_column($appointment['items'], 'startTime')));
    $end = collect($appointment['items'])->map(fn ($item) => \Carbon\CarbonImmutable::parse($item['startTime'])->addMinutes($item['durationSnapshot']))->max();
@endphp
<x-dialog :id="'appointment-'.$appointment['id']" title="Detalhes do agendamento" panel>
    <div class="flex-1 overflow-y-auto">
        <div class="space-y-3 border-b border-slate-100 px-4 py-4">
            <p class="text-xs text-slate-400">#{{ $appointment['id'] }}</p><x-status :status="$appointment['status']" />
            <div class="grid grid-cols-2 gap-3 text-sm"><div><p class="mb-0.5 text-[10px] font-medium tracking-wide text-slate-400">CLIENTE</p><p class="font-medium text-slate-800">{{ $client['name'] }}</p><p class="text-xs text-slate-400">{{ $client['phone'] }}</p></div><div><p class="mb-0.5 text-[10px] font-medium tracking-wide text-slate-400">PERÍODO</p><p>{{ $start->format('d/m/Y') }}</p><p class="text-xs text-slate-500">{{ $start->format('H:i') }} – {{ $end->format('H:i') }}</p></div></div>
            @if(!empty($appointment['notes']))<div class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"><p class="mb-1 text-[10px] text-slate-400">OBSERVAÇÕES</p>{{ $appointment['notes'] }}</div>@endif
            @if(!empty($appointment['cancellationReason']))<div class="rounded-lg bg-red-50 p-3 text-xs text-red-700"><p class="mb-1 text-[10px] text-red-400">MOTIVO DO CANCELAMENTO</p>{{ $appointment['cancellationReason'] }}</div>@endif
        </div>
        <div class="space-y-2 px-4 py-3"><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Itens do atendimento</h3>
            @foreach($appointment['items'] as $item)
                @php $professional = collect($professionals)->firstWhere('id', $item['professionalId']); @endphp
                <article class="rounded-lg border border-slate-100 p-3" style="border-left: 3px solid {{ $professional['color'] }}">
                    <div class="flex items-start justify-between gap-2"><div class="min-w-0"><p class="text-sm font-semibold text-slate-800">{{ $item['serviceNameSnapshot'] }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $professional['name'] }}</p></div><div class="shrink-0 text-right"><p class="text-sm font-semibold text-slate-700">R$ {{ number_format($item['priceSnapshot'], 2, ',', '.') }}</p><p class="text-xs text-slate-400">{{ $item['durationSnapshot'] }} min</p></div></div>
                    <div class="mt-2 flex gap-3 border-t border-slate-50 pt-2 text-xs text-slate-500"><span>{{ \Carbon\CarbonImmutable::parse($item['startTime'])->format('d/m/Y') }}</span><span>{{ \Carbon\CarbonImmutable::parse($item['startTime'])->format('H:i') }} – {{ \Carbon\CarbonImmutable::parse($item['startTime'])->addMinutes($item['durationSnapshot'])->format('H:i') }}</span></div>
                </article>
            @endforeach
            <div class="flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm"><span class="text-slate-500">{{ count($appointment['items']) }} item(s) · Total</span><strong>R$ {{ number_format(array_sum(array_column($appointment['items'], 'priceSnapshot')), 2, ',', '.') }}</strong></div>
        </div>
    </div>
    @if(in_array($appointment['status'], ['scheduled', 'confirmed']))
        <footer class="shrink-0 space-y-2 border-t border-slate-100 px-4 py-3">
            <div class="flex gap-2">@if($appointment['status'] === 'scheduled')<button type="button" data-preview-action class="secondary-button flex-1 border-blue-200 bg-blue-50 text-blue-700"><x-icon name="check" size="14" />Confirmar</button>@endif<button type="button" data-reschedule="{{ $appointment['id'] }}" class="secondary-button flex-1"><x-icon name="calendar" size="14" />Reagendar</button></div>
            <div class="flex gap-2"><button type="button" data-confirm="Concluir atendimento" data-confirm-message="Confirmar conclusão do atendimento?" class="primary-button flex-1 bg-green-600">Concluir</button><button type="button" data-confirm="Registrar não comparecimento" data-confirm-message="Registrar não comparecimento?" class="secondary-button flex-1 border-orange-200 bg-orange-50 text-orange-700">Não veio</button><button type="button" data-confirm="Cancelar agendamento" data-confirm-message="Confirmar cancelamento?" data-cancel-appointment class="secondary-button flex-1 border-red-200 bg-red-50 text-red-600">Cancelar</button></div>
        </footer>
    @endif
</x-dialog>
@endforeach
