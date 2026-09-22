@extends('layouts.app')

@section('content')
<div class="flex h-full min-h-0 flex-col bg-slate-50">
    <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="font-display text-lg font-semibold">Serviços</h1>
            <button type="button" data-open="create-service" class="primary-button"><x-icon name="plus" size="15" />Novo serviço</button>
        </div>
        <form action="{{ route('services.index') }}" method="GET" class="mt-3 space-y-2" role="search">
            <div class="flex gap-2"><input type="search" name="search" value="{{ $search }}" maxlength="255" class="input-base" aria-label="Buscar serviços por nome" placeholder="Buscar por nome…"><button type="submit" class="secondary-button">Buscar</button></div>
            @if($showInactive)<input type="hidden" name="show_inactive" value="1">@endif
            <div class="flex items-center gap-2">
                <button type="submit" name="show_inactive" value="{{ $showInactive ? '0' : '1' }}" aria-pressed="{{ $showInactive ? 'true' : 'false' }}" class="small-button rounded-full">{{ $showInactive ? 'Ocultar inativos' : 'Mostrar inativos' }}</button>
                <span class="text-xs text-slate-400">{{ $services->total() }} registro(s)</span>
                @if($search !== '')<a href="{{ route('services.index', $showInactive ? ['show_inactive' => 1] : []) }}" class="small-button">Limpar busca</a>@endif
            </div>
        </form>
        @if(session('success'))<div class="mt-3"><x-flash /></div>@endif
    </header>
    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3 md:px-6">
        @forelse($services as $service)
            <article class="rounded-xl border border-slate-200 bg-white p-3.5 {{ $service->active ? '' : 'opacity-60' }}">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-600"><x-icon name="scissors" size="16" /></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2"><p class="truncate text-sm font-semibold text-slate-800">{{ $service->name }}</p>@unless($service->active)<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Inativo</span>@endunless</div>
                        @if($service->description)<p class="truncate text-xs text-slate-500">{{ $service->description }}</p>@endif
                        <p class="mt-1 text-xs text-slate-600">{{ $service->duration_minutes }} min · R$ {{ number_format($service->price_cents / 100, 2, ',', '.') }}</p>
                        <div class="mt-1 flex flex-wrap gap-1">@foreach($service->professionals as $professional)<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600">{{ $professional->name }}</span>@endforeach</div>
                    </div>
                    @if($service->active)
                        <button type="button" data-open="edit-service-{{ $service->id }}" class="small-button">Editar</button>
                        <button type="button" data-open="deactivate-service-{{ $service->id }}" class="small-button text-red-600">Desativar</button>
                    @else
                        <button type="button" data-open="restore-service-{{ $service->id }}" class="small-button">Reativar</button>
                    @endif
                </div>
            </article>
            @if($service->active)
                @include('services.form', ['service' => $service])
                <x-dialog :id="'deactivate-service-'.$service->id" title="Desativar serviço">
                    <form action="{{ route('services.destroy', $service) }}" method="POST" class="space-y-4 p-6">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm text-slate-600">Deseja desativar <strong>{{ $service->name }}</strong>? O registro será preservado.</p>
                        <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="danger-button flex-1">Desativar</button></div>
                    </form>
                </x-dialog>
            @else
                <x-dialog :id="'restore-service-'.$service->id" title="Reativar serviço">
                    <form action="{{ route('services.restore', $service) }}" method="POST" class="space-y-4 p-6">
                        @csrf
                        @method('PATCH')
                        <p class="text-sm text-slate-600">Deseja reativar <strong>{{ $service->name }}</strong>?</p>
                        <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Reativar</button></div>
                    </form>
                </x-dialog>
            @endif
        @empty
            <p class="py-16 text-center text-sm text-slate-500">{{ $search !== '' || $showInactive ? 'Nenhum resultado para os filtros aplicados.' : 'Nenhum serviço cadastrado.' }}</p>
        @endforelse
    </div>
    @if($services->hasPages())<footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 md:px-6">{{ $services->links() }}</footer>@endif
</div>
@include('services.form', ['service' => null])
@endsection
