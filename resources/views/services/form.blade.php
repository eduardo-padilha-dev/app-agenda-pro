@php
    $editing = $service !== null;
    $formHasErrors = $errors->any() && (string) old('_editing_service', '') === ($editing ? (string) $service->id : '');
    $dialogId = $editing ? 'edit-service-'.$service->id : 'create-service';
    $selectedProfessionals = $formHasErrors ? old('professional_ids', []) : ($editing ? $service->professionals->pluck('id')->all() : []);
@endphp
<x-dialog :id="$dialogId" :title="$editing ? 'Editar serviço' : 'Novo serviço'" panel>
    <form action="{{ $editing ? route('services.update', $service) : route('services.store') }}" method="POST" novalidate class="flex min-h-0 flex-1 flex-col">
        @csrf
        @if($editing)
            @method('PATCH')
            <input type="hidden" name="_editing_service" value="{{ $service->id }}">
        @endif
        <div class="flex-1 space-y-4 overflow-y-auto p-4">
            <div>
                <x-field label="Nome" name="name" :value="$formHasErrors ? old('name') : ($editing ? $service->name : '')" maxlength="255" required />
                @if($formHasErrors)
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Descrição (opcional)<textarea name="description" rows="2" class="input-base mt-1">{{ $formHasErrors ? old('description') : ($editing ? $service->description : '') }}</textarea></label>
                @if($formHasErrors)
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-field label="Duração (min)" name="duration_minutes" type="number" min="1" :value="$formHasErrors ? old('duration_minutes') : ($editing ? $service->duration_minutes : '')" required />
                    @if($formHasErrors)
                        @error('duration_minutes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
                <div>
                    <x-field label="Preço (R$)" name="price" type="number" min="0" max="9999999.99" step="0.01" :value="$formHasErrors ? old('price') : ($editing ? number_format($service->price_cents / 100, 2, '.', '') : '')" required />
                    @if($formHasErrors)
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-slate-700">Profissionais que realizam</legend>
                @forelse($professionals as $professional)
                    <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="professional_ids[]" value="{{ $professional->id }}" @checked(in_array($professional->id, $selectedProfessionals))>{{ $professional->name }}</label>
                @empty
                    <p class="text-xs text-slate-500">Cadastre um profissional para associá-lo ao serviço.</p>
                @endforelse
                @if($formHasErrors)
                    @error('professional_ids')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @foreach($errors->get('professional_ids.*') as $messages)
                        @foreach($messages as $message)
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @endforeach
                    @endforeach
                @endif
            </fieldset>
        </div>
        <footer class="flex shrink-0 gap-2 border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Salvar</button></footer>
    </form>
</x-dialog>
@if($formHasErrors)
    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById(@json($dialogId)).showModal());</script>
@endif
