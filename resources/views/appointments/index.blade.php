@extends('layouts.app')
@section('content')
<div class="flex h-full flex-col bg-slate-50" data-appointment-list>
    <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
        <div class="mb-3 flex items-center justify-between"><h1 class="font-display text-lg font-semibold">Agendamentos</h1><button type="button" data-new-appointment class="primary-button py-1.5"><x-icon name="plus" size="15" />Novo</button></div>
        <div class="relative mb-3"><x-icon name="search" size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input data-search type="search" class="input-base pl-9" placeholder="Buscar por cliente…" aria-label="Buscar por cliente"></div>
        <div class="flex gap-2 overflow-x-auto pb-1">
            <select data-status-filter aria-label="Status" class="small-button bg-white"><option value="">Todos</option>@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
            <select data-professional-filter aria-label="Profissional" class="small-button bg-white"><option value="">Todos os profissionais</option>@foreach($professionals as $professional)<option value="{{ $professional['id'] }}">{{ $professional['name'] }}</option>@endforeach</select>
            <select data-period-filter aria-label="Período" class="small-button bg-white"><option value="">Qualquer período</option><option value="1">Hoje</option><option value="7">Próximos 7 dias</option><option value="30">Próximos 30 dias</option></select>
        </div>
    </header>
    <div class="flex-1 space-y-2 overflow-y-auto px-4 py-3 md:px-6">
        @foreach(collect($appointments)->sortByDesc(fn ($appointment) => min(array_column($appointment['items'], 'startTime'))) as $appointment)
            @php
                $client = collect($clients)->firstWhere('id', $appointment['clientId']);
                $start = \Carbon\CarbonImmutable::parse(min(array_column($appointment['items'], 'startTime')));
                $end = collect($appointment['items'])->map(fn ($item) => \Carbon\CarbonImmutable::parse($item['startTime'])->addMinutes($item['durationSnapshot']))->max();
            @endphp
            <button type="button" data-record data-search-text="{{ $client['name'] }}" data-status="{{ $appointment['status'] }}" data-professionals="{{ implode(',', array_column($appointment['items'], 'professionalId')) }}" data-date="{{ $start->format('Y-m-d') }}" data-open="appointment-{{ $appointment['id'] }}" class="block w-full rounded-xl border border-slate-200 bg-white p-3.5 text-left transition hover:border-slate-300 hover:shadow-sm">
                <div class="mb-2 flex items-start justify-between gap-2"><div><p class="text-sm font-semibold text-slate-800">{{ $client['name'] }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $start->format('d/m/Y') }} · {{ $start->format('H:i') }} – {{ $end->format('H:i') }}</p></div><x-status :status="$appointment['status']" /></div>
                <div class="space-y-1">@foreach($appointment['items'] as $item)@php $professional = collect($professionals)->firstWhere('id', $item['professionalId']); @endphp<div class="flex items-center gap-1.5 text-xs"><span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $professional['color'] }}"></span><span class="text-slate-600">{{ $item['serviceNameSnapshot'] }}</span><span class="text-slate-400">· {{ explode(' ', $professional['name'])[0] }}</span></div>@endforeach</div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-50 pt-2 text-xs text-slate-400"><span>{{ count($appointment['items']) }} item(s) · <strong class="font-medium text-slate-600">R$ {{ number_format(array_sum(array_column($appointment['items'], 'priceSnapshot')), 2, ',', '.') }}</strong></span><x-icon name="chevron-right" size="14" /></div>
            </button>
        @endforeach
        <p data-empty hidden class="py-16 text-center text-sm text-slate-500">Nenhum resultado para os filtros aplicados.</p>
    </div>
</div>
@endsection
