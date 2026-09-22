@foreach($professionals as $professional)
<x-dialog :id="'availability-'.$professional['id']" title="Disponibilidade" panel>
    <form method="dialog" data-preview-form="availability" class="flex min-h-0 flex-1 flex-col">
        <p class="px-4 pt-3 text-xs text-slate-400">{{ $professional['name'] }}</p>
        <div class="flex-1 space-y-2 overflow-y-auto p-4">
            @foreach(['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'] as $dayIndex => $dayName)
                @php $intervals = collect($availabilities)->first(fn ($availability) => $availability['professionalId'] === $professional['id'] && $availability['dayOfWeek'] === $dayIndex)['intervals'] ?? []; @endphp
                <fieldset data-availability-day class="overflow-hidden rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between bg-slate-50 px-3 py-2.5">
                        <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700"><input type="checkbox" data-day-enabled @checked(count($intervals))>{{ $dayName }}</label>
                        <button type="button" data-add-interval class="text-xs font-medium text-teal-600" @if(!count($intervals)) hidden @endif>+ Intervalo</button>
                    </div>
                    <p data-day-off class="px-3 py-2 text-xs text-slate-400" @if(count($intervals)) hidden @endif>Sem atendimento</p>
                    <div data-intervals>
                        @foreach($intervals as $interval)
                            @include('professionals.interval', ['start' => $interval['start'], 'end' => $interval['end']])
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
        <footer class="flex gap-2 border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Salvar disponibilidade</button></footer>
    </form>
</x-dialog>
@endforeach
<template id="interval-template">@include('professionals.interval', ['start' => '09:00', 'end' => '18:00'])</template>
