@php
    $blockHasErrors = $errors->any() && (string) old('_creating_block', '') === (string) $professional->id;
    $dialogId = 'blocks-real-'.$professional->id;
    $businessTimezone = config('app.business_timezone');
@endphp
<x-dialog :id="$dialogId" title="Bloqueios" panel>
    <p class="px-4 pt-3 text-xs text-slate-500">{{ $professional->name }} · horários locais</p>
    <div class="flex-1 space-y-4 overflow-y-auto p-4">
        <details class="group rounded-lg border border-dashed border-slate-200 p-3" @if($blockHasErrors) open @endif>
            <summary class="flex cursor-pointer items-center justify-center gap-2 text-sm text-slate-600"><x-icon name="plus" size="15" />Adicionar bloqueio</summary>
            <form action="{{ route('professionals.blocks.store', $professional) }}" method="POST" novalidate class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="_creating_block" value="{{ $professional->id }}">
                <div class="grid gap-2 sm:grid-cols-2">
                    <div>
                        <x-field label="Início" type="datetime-local" name="starts_at" :value="$blockHasErrors ? old('starts_at') : ''" required />
                        @if($blockHasErrors)
                            @error('starts_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <x-field label="Término" type="datetime-local" name="ends_at" :value="$blockHasErrors ? old('ends_at') : ''" required />
                        @if($blockHasErrors)
                            @error('ends_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>
                <div>
                    <x-field label="Motivo (opcional)" name="reason" :value="$blockHasErrors ? old('reason') : ''" placeholder="Consulta médica, reunião…" />
                    @if($blockHasErrors)
                        @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
                <div class="flex gap-2"><button type="button" data-close-details class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Adicionar</button></div>
            </form>
        </details>
        @forelse($professional->blocks as $block)
            <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                <x-icon name="lock" size="14" class="mt-0.5 text-slate-400" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-700">{{ $block->starts_at->setTimezone($businessTimezone)->format('d/m/Y H:i') }} – {{ $block->ends_at->setTimezone($businessTimezone)->format('d/m/Y H:i') }}</p>
                    @if($block->reason)<p class="mt-0.5 text-xs text-slate-500">{{ $block->reason }}</p>@endif
                </div>
                <form action="{{ route('professionals.blocks.destroy', [$professional, $block]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-button" aria-label="Remover bloqueio de {{ $professional->name }}" onclick="return confirm('Remover este bloqueio da agenda?')"><x-icon name="x" size="14" /></button>
                </form>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-500">Nenhum bloqueio cadastrado.</p>
        @endforelse
    </div>
    <footer class="border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button w-full">Concluir</button></footer>
</x-dialog>
@if($blockHasErrors)
    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById(@json($dialogId)).showModal());</script>
@endif
