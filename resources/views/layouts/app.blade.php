@extends('layouts.base')
@section('body')
@php
    $navigation = ['agenda' => ['Agenda', 'calendar'], 'agendamentos' => ['Agendamentos', 'list'], 'clientes' => ['Clientes', 'users'], 'profissionais' => ['Profissionais', 'user'], 'servicos' => ['Serviços', 'scissors']];
@endphp
<div class="flex h-full flex-col">
    <div class="hidden bg-orange-500 px-4 py-2 text-center text-sm text-white" data-offline>Sem conexão. Operações de escrita estão bloqueadas.</div>
    <div class="flex min-h-0 flex-1">
        <aside class="hidden w-56 shrink-0 flex-col border-r border-slate-200 bg-white md:flex">
            <div class="border-b border-slate-100 px-4 py-4"><x-brand /></div>
            @include('prototype.navigation')
            <div class="mt-auto border-t border-slate-100 p-3">
                @if(request()->routeIs('prototype'))
                    <p class="px-3 pb-2 text-xs text-slate-400">Protótipo — dados fictícios</p>
                @endif

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-red-50 hover:text-red-600">
                        Sair da conta
                    </button>
                </form>


            </div>
        </aside>
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 md:hidden">
                <button type="button" data-open="mobile-menu" class="icon-button" aria-label="Abrir menu"><x-icon name="menu" /></button>
                <x-brand />
                @if(request()->routeIs('prototype'))<span class="ml-auto text-[10px] text-slate-400">Protótipo</span>@endif
            </header>
            <main class="min-h-0 flex-1 overflow-hidden" id="main-content">@yield('content')</main>
            <nav class="flex shrink-0 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Navegação principal">
                @foreach($navigation as $key => [$label, $icon])
                    <a href="{{ match($key) { 'agenda' => route('calendar.index'), 'agendamentos' => route('appointments.index'), 'clientes' => route('clients.index'), 'profissionais' => route('professionals.index'), 'servicos' => route('services.index') } }}" @if($page === $key) aria-current="page" @endif class="flex min-w-0 flex-1 flex-col items-center gap-1 py-2 {{ $page === $key ? 'text-teal-600' : 'text-slate-400' }}"><x-icon :name="$icon" /><span class="text-[10px]">{{ $label }}</span></a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
<x-dialog id="mobile-menu" title="Agenda Pro">
    <form action="{{ route('logout') }}" method="POST" class="m-4">
        @csrf
        <button type="submit" class="text-sm text-slate-500">Sair da conta</button>
    </form>
</x-dialog>
@if(request()->routeIs('prototype') && in_array($page, ['agenda', 'agendamentos']))
    @include('appointments.form')
    @include('appointments.details')
@elseif(request()->routeIs('calendar.index'))
    @include('appointments.calendar-details')
@endif
<x-dialog id="confirm-action" title="Confirmar ação">
@if(request()->routeIs('prototype'))
    <div class="space-y-4 p-6">
        <p data-confirm-message class="text-sm text-slate-600"></p>
        <label data-cancellation-reason hidden class="block text-sm text-slate-600">Motivo do cancelamento (opcional)<textarea class="input-base mt-2" rows="2"></textarea></label>
        <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Voltar</button><button type="button" data-preview-action class="danger-button flex-1">Confirmar</button></div>
    </div>
</x-dialog>
@endif
@isset($prototypeData)
    <script id="prototype-data" type="application/json">@json($prototypeData)</script>
@endisset
@endsection
