@props(['status'])
@php
    $labels = ['scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'completed' => 'Concluído', 'cancelled' => 'Cancelado', 'no_show' => 'Não compareceu'];
@endphp
<span {{ $attributes->merge(['class' => 'status-badge status-'.$status]) }}><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $labels[$status] ?? $status }}</span>
