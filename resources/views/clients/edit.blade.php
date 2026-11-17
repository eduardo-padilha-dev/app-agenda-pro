@php
    $editing = (string) old('_editing_client') === (string) $client->id;
@endphp

<x-dialog :id="'edit-client-'.$client->id" title="Editar cliente" panel>
    <form action="{{ route('clients.update', $client) }}" method="POST" novalidate class="flex min-h-0 flex-1 flex-col">
        @csrf
        @method('PATCH')

    <input type="hidden" name="_editing_client" value="{{ $client->id }}">
        <div class="flex-1 space-y-4 overflow-y-auto p-4">
            <div>
                <x-field label="Nome" name="name"
                         :value="$editing ? old('name') : $client->name" maxlength="255" required />
                @if($editing)
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div>
                <x-field label="Telefone" name="phone" type="tel"
                         :value="$editing ? old('phone') : $client->phone" />
                @if($editing)
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div>
                <x-field label="E-mail (opcional)" name="email" type="email"
                         :value="$editing ? old('email') : $client->email" />
                @if($editing)
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Observações (opcional)
                    <textarea name="notes" rows="3"
                              class="input-base mt-1">{{ $editing ? old('notes') : $client->notes }}</textarea>
                </label>
                @if($editing)
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </div>

        <footer class="flex shrink-0 gap-2 border-t border-slate-100 p-4">
            <button type="button" data-close class="secondary-button flex-1">
                Cancelar
            </button>
            <button type="submit" class="primary-button flex-1">
                Salvar
            </button>
        </footer>
    </form>
</x-dialog>

@if($editing && $errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('edit-client-{{ $client->id }}').showModal();
        });
    </script>
@endif
