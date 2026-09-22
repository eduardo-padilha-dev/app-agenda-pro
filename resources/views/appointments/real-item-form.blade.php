<fieldset data-real-appointment-item class="space-y-3 rounded-lg border border-slate-200 bg-white p-3.5">
    <div class="flex items-center justify-between"><legend class="text-xs font-semibold uppercase tracking-wide text-slate-500">Serviço do atendimento</legend><button type="button" data-real-remove-item class="small-button">Remover</button></div>
    <label class="block text-sm font-medium text-slate-700">Serviço <span class="text-red-500">*</span>
        <select name="items[{{ $itemIndex }}][service_id]" class="input-base mt-1" required>
            <option value="">Selecionar serviço…</option>
            @foreach($services as $service)<option value="{{ $service->id }}" @selected((string) ($item['service_id'] ?? '') === (string) $service->id)>{{ $service->name }} — {{ $service->duration_minutes }} min — R$ {{ number_format($service->price_cents / 100, 2, ',', '.') }}</option>@endforeach
        </select>
    </label>
    <label class="block text-sm font-medium text-slate-700">Profissional <span class="text-red-500">*</span>
        <select name="items[{{ $itemIndex }}][professional_id]" class="input-base mt-1" required>
            <option value="">Selecionar profissional…</option>
            @foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string) ($item['professional_id'] ?? '') === (string) $professional->id)>{{ $professional->name }}</option>@endforeach
        </select>
    </label>
    <x-field label="Data e horário de início" :name="'items['.$itemIndex.'][starts_at]'" type="datetime-local" :value="$item['starts_at'] ?? ''" required />
</fieldset>
