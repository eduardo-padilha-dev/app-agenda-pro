@php
    $availabilityHasErrors = $errors->any() && (string) old('_editing_availability', '') === (string) $professional->id;
    $savedIntervals = $professional->availabilities->groupBy('weekday')->map(fn ($day) => $day->map(fn ($interval) => [
        'starts_at' => substr($interval->starts_at, 0, 5),
        'ends_at' => substr($interval->ends_at, 0, 5),
    ])->all())->all();
    $weekIntervals = $availabilityHasErrors ? old('intervals', []) : $savedIntervals;
    $dialogId = 'availability-real-'.$professional->id;
@endphp
<x-dialog :id="$dialogId" title="Disponibilidade" panel>
    <form action="{{ route('professionals.availability.update', $professional) }}" method="POST" data-availability-form class="flex min-h-0 flex-1 flex-col">
        @csrf
        @method('PUT')
        <input type="hidden" name="_editing_availability" value="{{ $professional->id }}">
        <p class="px-4 pt-3 text-xs text-slate-500">{{ $professional->name }} · horários locais recorrentes</p>
        <div class="flex-1 space-y-2 overflow-y-auto p-4">
            @if($availabilityHasErrors)
                @foreach($errors->all() as $message)
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @endforeach
            @endif
            @foreach(['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'] as $dayIndex => $dayName)
                @php $dayIntervals = $weekIntervals[$dayIndex] ?? []; @endphp
                <fieldset data-availability-day data-day-index="{{ $dayIndex }}" class="overflow-hidden rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between bg-slate-50 px-3 py-2.5">
                        <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700"><input type="checkbox" data-day-enabled @checked(count($dayIntervals))>{{ $dayName }}</label>
                        <button type="button" data-add-interval class="text-xs font-medium text-teal-600" @if(!count($dayIntervals)) hidden @endif>+ Intervalo</button>
                    </div>
                    <p data-day-off class="px-3 py-2 text-xs text-slate-400" @if(count($dayIntervals)) hidden @endif>Sem atendimento</p>
                    <div data-intervals>
                        @foreach($dayIntervals as $intervalIndex => $interval)
                            @include('professionals.interval', ['start' => $interval['starts_at'] ?? '', 'end' => $interval['ends_at'] ?? '', 'dayIndex' => $dayIndex, 'intervalIndex' => $intervalIndex, 'realInterval' => true])
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
        <footer class="flex gap-2 border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Salvar disponibilidade</button></footer>
    </form>
</x-dialog>
@if($availabilityHasErrors)
    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById(@json($dialogId)).showModal());</script>
@endif
