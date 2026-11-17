@extends('layouts.app')

@section('content')
    <div class="flex h-full min-h-0 flex-col bg-slate-50">
        <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
            <div class="flex items-center justify-between gap-3">
                <h1 class="font-display text-lg font-semibold">{{ $title }}</h1>

                <button type="button" data-open="create-client" class="primary-button">
                    <x-icon name="plus" size="15" />
                    Novo cliente
                </button>
            </div>

            <form action="{{ route('clients.index') }}" method="GET" class="mt-3 space-y-2" role="search">
                <div class="flex gap-2">
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input type="search" name="search" value="{{ $search }}" maxlength="255"
                               class="input-base pl-9" aria-label="Buscar clientes por nome ou telefone"
                               placeholder="Buscar por nome ou telefone…">
                    </div>
                    <button type="submit" class="secondary-button">Buscar</button>
                </div>
                @if($showInactive)
                    <input type="hidden" name="show_inactive" value="1">
                @endif
                <div class="flex items-center gap-2">
                    <button type="submit" formaction="{{ route('clients.index') }}"
                            name="show_inactive" value="{{ $showInactive ? '0' : '1' }}"
                            aria-pressed="{{ $showInactive ? 'true' : 'false' }}" class="small-button rounded-full">
                        {{ $showInactive ? 'Ocultar inativos' : 'Mostrar inativos' }}
                    </button>
                    <span class="text-xs text-slate-400">{{ $clients->total() }} registro(s)</span>
                    @if($search !== '')
                        <a href="{{ route('clients.index', $showInactive ? ['show_inactive' => 1] : []) }}" class="small-button">Limpar busca</a>
                    @endif
                </div>
            </form>

            @if(session('success'))
                <div class="mt-3">
                    <x-flash />
                </div>
            @endif
        </header>

        {{-- Apenas a lista rola --}}
        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3 md:px-6">
            @forelse($clients as $client)
                <article class="rounded-xl border border-slate-200 bg-white p-3.5 {{ $client->trashed() ? 'opacity-60' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <x-icon name="user" size="16" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $client->name }}</p>
                                @if($client->trashed())
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Inativo</span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500">
                                {{ $client->phone }}
                                @if($client->email)
                                    · {{ $client->email }}
                                @endif
                            </p>
                        </div>

                        @if($client->trashed())
                            <button type="button" data-open="restore-client-{{ $client->id }}" class="small-button">
                                Reativar
                            </button>
                        @else
                            <button type="button" data-open="edit-client-{{ $client->id }}" class="icon-button text-xs font-medium">
                                Editar
                            </button>
                            <button type="button"
                                    data-open="deactivate-client-{{ $client->id }}"
                                    class="icon-button hover:text-red-600"
                                    aria-label="Desativar {{ $client->name }}">
                                <x-icon name="user-x" size="16" />
                            </button>
                        @endif

                    </div>
                </article>
                @if($client->trashed())
                    @include('clients.restore', ['client' => $client])
                @else
                    @include('clients.deactivate', ['client' => $client])
                    @include('clients.edit', ['client' => $client])
                @endif
            @empty
                <p class="py-16 text-center text-sm text-slate-500">
                    {{ $search !== '' || $showInactive ? 'Nenhum resultado para os filtros aplicados.' : 'Nenhum cliente cadastrado.' }}
                </p>
            @endforelse
        </div>

        @if($clients->hasPages())
            <footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 md:px-6">
                {{ $clients->links() }}
            </footer>
        @endif
    </div>
@include('clients.create')
@endsection
