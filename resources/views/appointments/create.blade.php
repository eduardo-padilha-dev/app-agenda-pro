@extends('layouts.app')

@section('content')
<div class="flex h-full min-h-0 flex-col bg-slate-50">
    <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-4 md:px-6">
        <div class="flex items-center gap-3"><a href="{{ route('appointments.index') }}" class="small-button">Voltar</a><h1 class="font-display text-lg font-semibold">Novo agendamento</h1></div>
    </header>
    <form action="{{ route('appointments.store') }}" method="POST" data-real-appointment-form class="flex min-h-0 flex-1 flex-col">
        @csrf
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-4 py-4 md:px-6">
            @if($errors->any())
                <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">
                    @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
                </div>
            @endif
            <div>
                <label for="appointment-client" class="block text-sm font-medium text-slate-700">Cliente <span class="text-red-500">*</span></label>
                <select id="appointment-client" name="client_id" class="input-base mt-1" required>
                    <option value="">Selecionar cliente…</option>
                    @foreach($clients as $client)<option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>@endforeach
                </select>
            </div>
            <label class="block text-sm font-medium text-slate-700">Observações (opcional)<textarea name="notes" class="input-base mt-1 resize-none" rows="2">{{ old('notes') }}</textarea></label>
            <div class="flex items-center justify-between"><h2 class="text-sm font-semibold text-slate-700">Itens do atendimento</h2><button type="button" data-real-add-item class="small-button"><x-icon name="plus" size="13" />Adicionar item</button></div>
            <div data-real-appointment-items class="space-y-3">
                @foreach(old('items', [['service_id' => '', 'professional_id' => '', 'starts_at' => $initialStart]]) as $itemIndex => $item)
                    @include('appointments.real-item-form', ['itemIndex' => $itemIndex, 'item' => $item])
                @endforeach
            </div>
        </div>
        <footer class="flex shrink-0 gap-2 border-t border-slate-200 bg-white p-4 md:px-6"><a href="{{ route('appointments.index') }}" class="secondary-button flex-1 text-center">Cancelar</a><button type="submit" class="primary-button flex-1">Criar agendamento</button></footer>
    </form>
</div>
<template id="real-appointment-item-template">@include('appointments.real-item-form', ['itemIndex' => '__INDEX__', 'item' => []])</template>
@endsection
