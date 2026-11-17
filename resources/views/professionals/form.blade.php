@php
    $editing = $professional !== null;
    $formHasErrors = $errors->any() && (string) old('_editing_professional', '') === ($editing ? (string) $professional->id : '');
    $dialogId = $editing ? 'edit-professional-'.$professional->id : 'create-professional';
@endphp
<x-dialog :id="$dialogId" :title="$editing ? 'Editar profissional' : 'Novo profissional'" panel>
    <form action="{{ $editing ? route('professionals.update', $professional) : route('professionals.store') }}" method="POST" novalidate class="flex min-h-0 flex-1 flex-col">
        @csrf
        @if($editing)
            @method('PATCH')
            <input type="hidden" name="_editing_professional" value="{{ $professional->id }}">
        @endif
        <div class="flex-1 space-y-4 overflow-y-auto p-4">
            @foreach([['name', 'Nome', 'text', true], ['phone', 'Telefone (opcional)', 'tel', false], ['email', 'E-mail (opcional)', 'email', false]] as [$field, $label, $type, $required])
                <div>
                    <x-field :label="$label" :name="$field" :type="$type" :required="$required" :value="$formHasErrors ? old($field) : ($editing ? $professional->$field : '')" maxlength="255" />
                    @if($formHasErrors)
                        @error($field)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            @endforeach
            <div>
                <label class="block text-sm font-medium text-slate-700">Observações (opcional)<textarea name="notes" rows="3" class="input-base mt-1">{{ $formHasErrors ? old('notes') : ($editing ? $professional->notes : '') }}</textarea></label>
                @if($formHasErrors)
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </div>
        <footer class="flex shrink-0 gap-2 border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Salvar</button></footer>
    </form>
</x-dialog>
@if($formHasErrors)
    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById(@json($dialogId)).showModal());</script>
@endif
