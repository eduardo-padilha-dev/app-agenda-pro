@foreach($appointments as $appointment)
@php
    $client = collect($clients)->firstWhere('id', $appointment['clientId']);
    $start = collect($appointment['items'])->map(fn ($item) => \Carbon\CarbonImmutable::parse($item['startTime']))->min();
    $end = collect($appointment['items'])->map(fn ($item) => \Carbon\CarbonImmutable::parse($item['endTime']))->max();
@endphp
<x-dialog :id="'appointment-'.$appointment['id']" title="Detalhes do agendamento" panel>
    <div class="flex-1 overflow-y-auto">
        <div class="space-y-3 border-b border-slate-100 px-4 py-4">
            <div class="flex items-center justify-between gap-2"><p class="text-xs text-slate-400">#{{ $appointment['id'] }}</p><x-status :status="$appointment['status']" /></div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><p class="mb-0.5 text-[10px] font-medium tracking-wide text-slate-400">CLIENTE</p><p class="font-medium text-slate-800">{{ $client['name'] }}</p><p class="text-xs text-slate-400">{{ $client['phone'] }}</p></div>
                <div><p class="mb-0.5 text-[10px] font-medium tracking-wide text-slate-400">PERÍODO</p><p>{{ $start->format('d/m/Y') }}</p><p class="text-xs text-slate-500">{{ $start->format('H:i') }} – {{ $end->format('H:i') }}</p></div>
            </div>
            @if(!empty($appointment['notes']))<div class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"><p class="mb-1 text-[10px] text-slate-400">OBSERVAÇÕES</p>{{ $appointment['notes'] }}</div>@endif
        </div>
        <div class="space-y-2 px-4 py-3">
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Itens do atendimento</h3>
            @foreach($appointment['items'] as $item)
                @php $professional = collect($professionals)->firstWhere('id', $item['professionalId']); @endphp
                <article class="rounded-lg border border-slate-100 p-3" style="border-left: 3px solid {{ $professional['color'] }}">
                    <div class="flex items-start justify-between gap-2"><div class="min-w-0"><p class="text-sm font-semibold text-slate-800">{{ $item['serviceNameSnapshot'] }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $professional['name'] }}</p></div><div class="shrink-0 text-right"><p class="text-sm font-semibold text-slate-700">R$ {{ number_format($item['priceSnapshot'], 2, ',', '.') }}</p><p class="text-xs text-slate-400">{{ $item['durationSnapshot'] }} min</p></div></div>
                    <div class="mt-2 flex gap-3 border-t border-slate-50 pt-2 text-xs text-slate-500"><span>{{ \Carbon\CarbonImmutable::parse($item['startTime'])->format('d/m/Y') }}</span><span>{{ \Carbon\CarbonImmutable::parse($item['startTime'])->format('H:i') }} – {{ \Carbon\CarbonImmutable::parse($item['endTime'])->format('H:i') }}</span></div>
                </article>
            @endforeach
            <div class="flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm"><span class="text-slate-500">{{ count($appointment['items']) }} item(s) · Total</span><strong>R$ {{ number_format(array_sum(array_column($appointment['items'], 'priceSnapshot')), 2, ',', '.') }}</strong></div>
        </div>
    </div>
    @if(in_array($appointment['status'], ['scheduled', 'confirmed']))
        <footer class="shrink-0 space-y-2 border-t border-slate-100 px-4 py-3">
            <div class="flex gap-2">
                @if($appointment['status'] === 'scheduled')
                    <form action="{{ route('appointments.confirm', $appointment['id']) }}" method="POST" class="flex-1">@csrf<button type="submit" class="secondary-button w-full border-blue-200 bg-blue-50 text-blue-700"><x-icon name="check" size="14" />Confirmar</button></form>
                @endif
                <a href="{{ route('appointments.edit', $appointment['id']) }}" class="secondary-button flex-1"><x-icon name="calendar" size="14" />Reagendar</a>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <form action="{{ route('appointments.complete', $appointment['id']) }}" method="POST">@csrf<button type="submit" class="primary-button w-full bg-green-600 hover:bg-green-700" onclick="return confirm('Confirmar conclusão do atendimento?')">Concluir</button></form>
                <form action="{{ route('appointments.no-show', $appointment['id']) }}" method="POST">@csrf<button type="submit" class="secondary-button w-full border-orange-200 bg-orange-50 text-orange-700" onclick="return confirm('Registrar não comparecimento?')">Não veio</button></form>
            </div>
            <form action="{{ route('appointments.cancel', $appointment['id']) }}" method="POST" class="space-y-2 rounded-lg border border-red-100 bg-red-50 p-2">
                @csrf
                <label class="block text-xs text-red-700">Motivo do cancelamento (opcional)<textarea name="cancellation_reason" rows="2" class="input-base mt-1"></textarea></label>
                <button type="submit" class="secondary-button w-full border-red-200 bg-white text-red-600" onclick="return confirm('Confirmar cancelamento?')">Cancelar agendamento</button>
            </form>
        </footer>
    @endif
</x-dialog>
@endforeach
