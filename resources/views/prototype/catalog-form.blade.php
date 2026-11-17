<x-dialog :id="'edit-'.$kind" :title="$newLabel" panel>
    <form method="dialog" data-preview-form="catalog" data-kind="{{ $kind }}" class="flex min-h-0 flex-1 flex-col">
        <div class="flex-1 space-y-4 overflow-y-auto p-4">
            <x-field label="Nome" name="name" placeholder="{{ $kind === 'services' ? 'Nome do serviço' : 'Nome completo' }}" required />
            @if($kind === 'services')
                <label class="block text-sm font-medium text-slate-700">Descrição<textarea name="description" class="input-base mt-1 resize-none" rows="2"></textarea></label>
                <div class="grid grid-cols-2 gap-3"><x-field label="Duração (min)" name="durationMinutes" type="number" min="1" value="60" required /><x-field label="Preço (R$)" name="price" type="number" min="0" step="0.01" value="0" required /></div>
                <fieldset class="space-y-2"><legend class="mb-2 text-sm font-medium text-slate-700">Profissionais que realizam</legend><p class="text-xs text-slate-400">Alterações de preço e duração não afetam agendamentos já existentes.</p>
                    @foreach(collect($professionals)->where('active', true) as $professional)<label class="flex items-center gap-2.5 text-sm text-slate-700"><input type="checkbox" name="professionalIds" value="{{ $professional['id'] }}"><span class="h-3 w-3 rounded-full" style="background: {{ $professional['color'] }}"></span>{{ $professional['name'] }}</label>@endforeach
                </fieldset>
            @else
                <x-field label="Telefone" name="phone" type="tel" placeholder="(11) 99999-9999" :required="$kind === 'clients'" />
                <x-field label="E-mail (opcional)" name="email" type="email" placeholder="email@exemplo.com" />
                @if($kind === 'professionals')
                    <fieldset><legend class="mb-2 text-sm font-medium text-slate-700">Cor na agenda</legend><div class="flex flex-wrap gap-3">@foreach(['#0d9488', '#7c3aed', '#db2777', '#d97706', '#2563eb', '#16a34a', '#dc2626', '#475569'] as $color)<label class="cursor-pointer"><input type="radio" name="color" value="{{ $color }}" class="peer sr-only" @checked($loop->first)><span class="block h-7 w-7 rounded-full ring-offset-2 peer-checked:ring-2 peer-checked:ring-slate-700 peer-focus-visible:ring-2" style="background: {{ $color }}"></span><span class="sr-only">{{ $color }}</span></label>@endforeach</div></fieldset>
                @endif
                <label class="block text-sm font-medium text-slate-700">Observações (opcional)<textarea name="notes" class="input-base mt-1 resize-none" rows="3" placeholder="{{ $kind === 'clients' ? 'Alergias, preferências…' : '' }}"></textarea></label>
                @if($kind === 'professionals')<fieldset class="space-y-2"><legend class="mb-2 text-sm font-medium text-slate-700">Serviços que realiza</legend>@foreach(collect($services)->where('active', true) as $service)<label class="flex items-center gap-2.5 text-sm text-slate-700"><input type="checkbox" name="serviceIds" value="{{ $service['id'] }}">{{ $service['name'] }}</label>@endforeach</fieldset>@endif
            @endif
            <label class="block text-sm font-medium text-slate-700" data-edit-only hidden>Situação<select name="active" class="input-base mt-1"><option value="true">Ativo</option><option value="false">Inativo</option></select></label>
        </div>
        <footer class="flex shrink-0 gap-2 border-t border-slate-100 p-4"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" class="primary-button flex-1">Salvar</button></footer>
    </form>
</x-dialog>
