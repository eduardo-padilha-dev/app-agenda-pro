@extends('layouts.app')
@section('content')
<div class="flex h-full flex-col bg-slate-50" data-catalog>
    <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
        <div class="mb-3 flex items-center justify-between gap-2"><h1 class="font-display text-lg font-semibold">{{ $title }}</h1><button type="button" data-edit="{{ $kind }}" class="primary-button py-1.5"><x-icon name="plus" size="15" />{{ $newLabel }}</button></div>
        <div class="relative"><x-icon name="search" size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input type="search" data-search class="input-base pl-9" aria-label="Buscar {{ mb_strtolower($title) }}" placeholder="{{ $placeholder }}"></div>
        <div class="mt-2 flex items-center gap-2"><button type="button" data-toggle-inactive aria-pressed="false" class="small-button rounded-full">Mostrar inativos</button><span data-result-count class="text-xs text-slate-400"></span></div>
    </header>
    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3 md:px-6">
        @foreach($records as $record)
            <article data-record data-search-text="{{ $record['name'].' '.($record['phone'] ?? '') }}" data-active="{{ $record['active'] ? 'true' : 'false' }}" @if(!$record['active']) hidden @endif class="rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-slate-300 {{ !$record['active'] ? 'opacity-60' : '' }}">
                <div class="flex items-center gap-3">
                    @if($kind === 'professionals')
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white" style="background: {{ $record['color'] }}">{{ mb_substr($record['name'], 0, 1) }}</div>
                    @else
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center {{ $kind === 'services' ? 'rounded-lg bg-teal-50 text-teal-600' : 'rounded-full bg-slate-100 text-slate-400' }}"><x-icon :name="$kind === 'services' ? 'scissors' : 'user'" size="16" /></div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><p class="truncate text-sm font-semibold text-slate-800">{{ $record['name'] }}</p>@if(!$record['active'])<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Inativo</span>@endif</div>
                        @if($kind === 'services')
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $record['description'] ?? '' }}</p>
                            <p class="mt-1.5 text-xs text-slate-600">{{ $record['durationMinutes'] }} min <span class="px-2 text-slate-300">·</span><strong>R$ {{ number_format($record['price'], 2, ',', '.') }}</strong><span class="px-2 text-slate-300">·</span><span class="text-slate-400">{{ collect($professionals)->whereIn('id', $record['professionalIds'])->pluck('name')->map(fn ($name) => explode(' ', $name)[0])->join(', ') }}</span></p>
                        @else
                            <p class="truncate text-xs text-slate-500">{{ $record['phone'] ?? '' }}@if($kind === 'clients' && !empty($record['email'])) · {{ $record['email'] }}@endif</p>
                            @if($kind === 'professionals')<div class="mt-1.5 flex flex-wrap gap-1">@foreach(collect($services)->whereIn('id', $record['serviceIds']) as $service)<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600">{{ $service['name'] }}</span>@endforeach</div>@endif
                        @endif
                    </div>
                    @if($kind === 'clients')
                        <button type="button" data-edit="clients" data-id="{{ $record['id'] }}" class="icon-button text-xs font-medium">Editar</button>
                        @if($record['active'])<button type="button" data-confirm="Desativar cliente" data-confirm-message="O cliente ficará indisponível para novos agendamentos. O histórico será preservado." class="icon-button hover:text-red-500" aria-label="Desativar {{ $record['name'] }}"><x-icon name="user-x" size="14" /></button>@endif
                    @endif
                </div>
                @if($kind !== 'clients')
                    <div class="mt-3 flex flex-wrap items-center gap-1 border-t border-slate-50 pt-3">
                        <button type="button" data-edit="{{ $kind }}" data-id="{{ $record['id'] }}" class="small-button">Editar</button>
                        @if($kind === 'professionals')
                            <button type="button" data-open="availability-{{ $record['id'] }}" class="small-button"><x-icon name="clock" size="11" />Disponibilidade</button>
                            <button type="button" data-open="blocks-{{ $record['id'] }}" class="small-button"><x-icon name="lock" size="11" />Bloqueios</button>
                        @endif
                        @if($record['active'])<button type="button" data-confirm="Desativar {{ $singular }}" data-confirm-message="O {{ $singular }} ficará indisponível para novos agendamentos. O histórico será preservado." class="ml-auto rounded-lg px-2.5 py-1 text-xs font-medium text-red-500 hover:bg-red-50">Desativar</button>@endif
                    </div>
                @endif
            </article>
        @endforeach
        <p data-empty hidden class="py-16 text-center text-sm text-slate-500">Nenhum resultado para os filtros aplicados.</p>
    </div>
</div>
@include('prototype.catalog-form')
@if($kind === 'professionals')
    @include('professionals.availability')
    @include('professionals.blocks')
@endif
@endsection
