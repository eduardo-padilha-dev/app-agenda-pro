@php($creating = !old('_editing_client'))
<x-dialog id="create-client" title="Novo cliente" panel>
    <form action="{{ route('clients.store') }}" method="POST"
          novalidate class="flex min-h-0 flex-1 flex-col">
        @csrf

        <div class="flex-1 space-y-4 overflow-y-auto p-4">
            <div>
                <x-field label="Nome" name="name"
                         :value="($creating ? old('name') : '')" maxlength="255" required />
                @if($creating)
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @endif
            </div>

            <div>
                <x-field label="Telefone" name="phone" type="tel"
                         :value="($creating ? old('phone') : '')" maxlength="255" required />
                @if($creating)
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @endif
            </div>

            <div>
                <x-field label="E-mail (opcional)" name="email" type="email"
                         :value="($creating ? old('email') : '')" maxlength="255" />
                @if($creating)
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Observações (opcional)
                    <textarea name="notes" rows="3"
                              class="input-base mt-1">{{ ($creating ? old('notes') : '') }}</textarea>
                </label>
                @if($creating)
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

@if($errors->any() && !old('_editing_client'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('create-client').showModal();
        });
    </script>
@endif