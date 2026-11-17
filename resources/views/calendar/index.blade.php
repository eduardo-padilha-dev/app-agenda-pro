@extends('layouts.app')
@php
    $calendarRoute = request()->routeIs('prototype') ? 'prototype' : 'calendar.index';
    $calendarParameters = request()->routeIs('prototype') ? ['page' => 'calendar'] : [];
    $calendarStartMinutes = $calendarStartMinutes ?? 480;
    $calendarEndMinutes = $calendarEndMinutes ?? 1260;
    $calendarHeight = $calendarHeight ?? 832;
@endphp
@section('content')
<div class="flex h-full flex-col bg-white" data-agenda data-calendar-start="{{ $calendarStartMinutes }}" data-calendar-end="{{ $calendarEndMinutes }}" @unless(request()->routeIs('prototype')) data-create-appointment-url="{{ route('appointments.create') }}" @endunless>
    <header class="flex shrink-0 flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-2.5">
        <h1 class="sr-only">Agenda</h1>
        <div class="flex items-center gap-1">
            <a href="{{ route($calendarRoute, [...$calendarParameters, 'date' => $date->subWeek()->format('Y-m-d')]) }}" data-date-navigation data-desktop-date="{{ $date->subWeek()->format('Y-m-d') }}" data-mobile-date="{{ $date->subDay()->format('Y-m-d') }}" class="icon-button" aria-label="Período anterior"><x-icon name="chevron-left" size="16" /></a>
            <a href="{{ route($calendarRoute, $calendarParameters) }}" class="small-button">Hoje</a>
            <a href="{{ route($calendarRoute, [...$calendarParameters, 'date' => $date->addWeek()->format('Y-m-d')]) }}" data-date-navigation data-desktop-date="{{ $date->addWeek()->format('Y-m-d') }}" data-mobile-date="{{ $date->addDay()->format('Y-m-d') }}" class="icon-button" aria-label="Próximo período"><x-icon name="chevron-right" size="16" /></a>
        </div>
        <p class="text-sm font-medium capitalize text-slate-700"><span class="hidden md:inline">{{ $week->format('d/m') }} – {{ $week->addDays(6)->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</span><span class="md:hidden">{{ $date->locale('pt_BR')->isoFormat('ddd, D [de] MMMM') }}</span></p>
        <div class="flex-1"></div>
        <div class="order-last flex w-full items-center gap-1.5 overflow-x-auto md:order-none md:w-auto" aria-label="Filtrar profissionais">
            @foreach($professionals as $professional)
                <button type="button" data-agenda-filter="{{ $professional['id'] }}" aria-pressed="true" class="flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium" style="border-color: {{ $professional['color'] }}; color: {{ $professional['color'] }}; background: {{ $professional['color'] }}18"><span class="h-2 w-2 rounded-full bg-current"></span>{{ explode(' ', $professional['name'])[0] }}</button>
            @endforeach
            <button type="button" data-clear-agenda-filter class="icon-button" aria-label="Limpar filtro" hidden><x-icon name="x" size="14" /></button>
        </div>
        @if(request()->routeIs('prototype'))
            <button type="button" data-new-appointment class="primary-button py-1.5"><x-icon name="plus" size="15" /><span class="hidden sm:inline">Novo agendamento</span><span class="sm:hidden">Novo</span></button>
        @else
            <a href="{{ route('appointments.create') }}" class="primary-button py-1.5"><x-icon name="plus" size="15" /><span class="hidden sm:inline">Novo agendamento</span><span class="sm:hidden">Novo</span></a>
        @endif
    </header>
    <nav class="flex shrink-0 justify-around gap-1 border-b border-slate-100 bg-slate-50 px-2 py-1.5 md:hidden" aria-label="Dias da semana">
        @for($i = 0; $i < 7; $i++)
            @php $day = $week->addDays($i); @endphp
            <a href="{{ route($calendarRoute, [...$calendarParameters, 'date' => $day->format('Y-m-d')]) }}" class="flex flex-col items-center rounded-lg px-2 py-1 {{ $day->isSameDay($date) ? 'bg-white shadow-sm' : '' }}" @if($day->isSameDay($date)) aria-current="date" @endif><span class="text-[10px] font-medium uppercase text-slate-400">{{ $day->locale('pt_BR')->isoFormat('ddd') }}</span><span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $day->isSameDay($date) ? 'bg-teal-600 text-white' : 'text-slate-600' }}">{{ $day->day }}</span></a>
        @endfor
    </nav>
    <div class="min-h-0 flex-1 overflow-y-auto" data-calendar-scroll>
        <div class="calendar-columns sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="py-3 text-center text-[10px] text-slate-400">{{ $timezoneLabel ?? 'GMT−3' }}</div>
            @for($i = 0; $i < 7; $i++)
                @php $day = $week->addDays($i); @endphp
                <div data-day="{{ $day->format('Y-m-d') }}" data-selected="{{ $day->isSameDay($date) ? 'true' : 'false' }}" class="border-l border-slate-100 py-2 text-center"><p class="text-[10px] font-medium uppercase {{ $day->isSameDay($today) ? 'text-teal-600' : 'text-slate-400' }}">{{ $day->locale('pt_BR')->isoFormat('ddd') }}</p><span class="mx-auto flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $day->isSameDay($today) ? 'bg-teal-600 text-white' : 'text-slate-600' }}">{{ $day->day }}</span></div>
            @endfor
        </div>
        <div class="calendar-columns">
            <div class="relative" style="height: {{ $calendarHeight }}px">@for($minutes = $calendarStartMinutes; $minutes < $calendarEndMinutes; $minutes += 60)<span class="absolute right-2 text-[10px] text-slate-400" style="top: {{ ($minutes - $calendarStartMinutes) / 60 * 64 }}px">{{ sprintf('%02d:00', intdiv($minutes, 60)) }}</span>@endfor</div>
            @for($i = 0; $i < 7; $i++)
                @php $day = $week->addDays($i); @endphp
                @include('calendar.day', ['day' => $day])
            @endfor
        </div>
    </div>
    <footer class="flex shrink-0 flex-wrap items-center gap-4 border-t border-slate-100 px-4 py-2 text-[10px] text-slate-400"><span>Clique em um horário para agendar</span><span class="ml-auto">Intervalos de 30 min</span></footer>
</div>
@endsection
