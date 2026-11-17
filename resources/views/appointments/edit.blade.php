@extends('layouts.app')

@section('content')
<div class="flex h-full min-h-0 flex-col bg-slate-50">
    <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
        <div class="flex items-center gap-3"><a href="{{ route('appointments.index') }}" class="small-button">Voltar</a><h1 class="font-display text-lg font-semibold">Reagendar atendimento</h1></div>
    </header>
    <form action="{{ route('appointments.update', $appointment) }}" method="POST" data-real-appointment-form class="flex min-h-0 flex-1 flex-col">
        @csrf
        @method('PUT')
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-4 py-4 md:px-6">
            @if($errors->any())
                <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">
                    @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
                </div>
            @endif
            @if($appointment->status === \App\Enums\AppointmentStatus::Confirmed)
                <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">Ao salvar, este atendimento voltará para o status Agendado e precisará de nova confirmação.</p>
            @endif
            <div>
                <p class="text-sm font-medium text-slate-700">Cliente</p>
                <p class="input-base mt-1 bg-slate-100 text-slate-600">{{ $appointment->client->name }}</p>
            </div>
            <label class="block text-sm font-medium text-slate-700">Observações (opcional)<textarea name="notes" class="input-base mt-1 resize-none" rows="2">{{ old('notes', $appointment->notes) }}</textarea></label>
            <div class="flex items-center justify-between"><h2 class="text-sm font-semibold text-slate-700">Itens do atendimento</h2><button type="button" data-real-add-item class="small-button"><x-icon name="plus" size="13" />Adicionar item</button></div>
            <div data-real-appointment-items class="space-y-3">
                @foreach(old('items', $appointment->items->map(fn ($item) => [
                    'service_id' => $item->service_id,
                    'professional_id' => $item->professional_id,
                    'starts_at' => $item->starts_at->setTimezone(config('app.business_timezone'))->format('Y-m-d\TH:i'),
                ])->all()) as $itemIndex => $item)
                    @include('appointments.real-item-form', ['itemIndex' => $itemIndex, 'item' => $item])
                @endforeach
            </div>
        </div>
        <footer class="flex shrink-0 gap-2 border-t border-slate-200 bg-white p-4 md:px-6"><a href="{{ route('appointments.index') }}" class="secondary-button flex-1 text-center">Cancelar</a><button type="submit" class="primary-button flex-1">Salvar reagendamento</button></footer>
    </form>
</div>
<template id="real-appointment-item-template">@include('appointments.real-item-form', ['itemIndex' => '__INDEX__', 'item' => []])</template>
@endsection
