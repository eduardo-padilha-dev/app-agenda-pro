@props(['id', 'title', 'panel' => false])
<dialog id="{{ $id }}" class="{{ $panel ? 'slide-panel' : 'modal' }}" aria-labelledby="{{ $id }}-title">
    <div class="flex h-full flex-col">
        <header class="flex shrink-0 items-center justify-between border-b border-slate-100 px-4 py-3.5">
            <h2 id="{{ $id }}-title" class="font-display text-base font-semibold text-slate-900">{{ $title }}</h2>
            <button type="button" class="icon-button" data-close aria-label="Fechar"><x-icon name="x" /></button>
        </header>
        <p data-dialog-message role="status" hidden class="mx-4 mt-3 shrink-0 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-600"></p>
        {{ $slot }}
    </div>
</dialog>
