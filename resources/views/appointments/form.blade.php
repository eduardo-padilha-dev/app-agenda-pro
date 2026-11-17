<x-dialog id="new-appointment" title="Novo agendamento" panel>
    <form method="dialog" data-preview-form="appointment" class="flex min-h-0 flex-1 flex-col">
        <p data-reschedule-note hidden class="mx-4 mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Ao reagendar um atendimento confirmado, ele voltará para o status Agendado e precisará de nova confirmação.</p>
        <div class="flex-1 space-y-4 overflow-y-auto p-4">
            <label class="block text-sm font-medium text-slate-700">Cliente <span class="text-red-500">*</span><select name="clientId" class="input-base mt-1" required><option value="">Selecionar cliente…</option>@foreach(collect($clients)->where('active', true) as $client)<option value="{{ $client['id'] }}">{{ $client['name'] }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Observações<textarea name="notes" class="input-base mt-1 resize-none" rows="2" placeholder="Observações sobre o atendimento…"></textarea></label>
            <div class="flex items-center justify-between"><h3 class="text-sm font-medium text-slate-700">Itens do atendimento</h3><button type="button" data-add-item class="flex items-center gap-1 text-xs font-medium text-teal-600"><x-icon name="plus" size="13" />Adicionar item</button></div>
            <div data-appointment-items class="space-y-3">@include('appointments.item-form')</div>
        </div>
        <footer class="shrink-0 space-y-3 border-t border-slate-100 p-4">
            <div class="flex items-center justify-between text-sm"><span data-item-count class="text-slate-500">1 item(s)</span><strong data-total>R$ 0,00</strong></div>
            <p data-form-error class="rounded-lg bg-red-50 p-3 text-xs text-red-700" role="alert" hidden></p>
            <div class="flex gap-2"><button type="button" data-close class="secondary-button flex-1">Cancelar</button><button type="submit" data-submit-label class="primary-button flex-1">Criar agendamento</button></div>
        </footer>
    </form>
</x-dialog>
<template id="appointment-item-template">@include('appointments.item-form')</template>
