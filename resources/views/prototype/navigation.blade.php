<nav class="space-y-1 p-3" aria-label="Menu">
    @foreach($navigation as $key => [$label, $icon])
        <a href="{{ match($key) { 'agenda' => route('calendar.index'), 'agendamentos' => route('appointments.index'), 'clientes' => route('clients.index'), 'profissionais' => route('professionals.index'), 'servicos' => route('services.index') } }}" @if($page === $key) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ $page === $key ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            <x-icon :name="$icon" />{{ $label }}
            @if($page === $key)<x-icon name="chevron-right" size="14" class="ml-auto opacity-70" />@endif
        </a>
    @endforeach
</nav>
