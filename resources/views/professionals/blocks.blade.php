@foreach($professionals as $professional)
<x-dialog :id="'blocks-'.$professional['id']" title="Bloqueios" panel>
    <p class="px-4 pt-3 text-xs text-slate-400">{{ $professional['name'] }}</p>
    <div class="flex-1 space-y-4 overflow-y-auto p-4">
        <details class="group rounded-lg border border-dashed border-slate-200 p-3">
            <summary class="flex items-center justify-center gap-2 text-sm text-slate-500"><x-icon name="plus" size="15" />Adicionar bloqueio</summary>
            <form method="dialog" data-preview-form="block" class="mt-4 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Novo bloqueio</p>
                <div class="grid gap-2 sm:grid-cols-2"><x-field label="Início" type="datetime-local" name="startTime" required /><x-field label="Término" type="datetime-local" name="endTime" required /></div>
                <x-field label="Motivo (opcional)" name="reason" placeholder="Consulta médica, reunião…" />
                <div class="flex gap-2"><button type="button" data-close-details class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Adicionar</button></div>
            </form>
        </details>
        @forelse(collect($blocks)->where('professionalId', $professional['id']) as $block)
            <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3"><x-icon name="lock" size="14" class="mt-0.5 text-slate-400" /><div class="flex-1"><p class="text-sm font-medium text-slate-700">{{ \Carbon\CarbonImmutable::parse($block['startTime'])->locale('pt_BR')->isoFormat('ddd, DD/MM') }}</p><p class="text-xs text-slate-500">{{ \Carbon\CarbonImmutable::parse($block['startTime'])->format('H:i') }} – {{ \Carbon\CarbonImmutable::parse($block['endTime'])->format('H:i') }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $block['reason'] }}</p></div><button type="button" data-confirm="Remover bloqueio" data-confirm-message="Remover este bloqueio da agenda?" class="icon-button" aria-label="Remover bloqueio"><x-icon name="x" size="14" /></button></div>
        @empty
            <p class="py-8 text-center text-sm text-slate-400">Nenhum bloqueio cadastrado.</p>
        @endforelse
    </div>
    <footer class="border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button w-full">Concluir</button></footer>
</x-dialog>
@endforeach
