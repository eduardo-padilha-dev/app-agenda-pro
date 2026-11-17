<div data-interval class="flex items-center gap-2 border-t border-slate-100 px-3 py-2">
    <input type="time" value="{{ $start }}" @if($realInterval ?? false) name="intervals[{{ $dayIndex }}][{{ $intervalIndex }}][starts_at]" @endif data-time-start class="min-w-0 rounded border border-slate-200 px-2 py-1 text-sm" aria-label="Início do intervalo" required>
    <span class="text-sm text-slate-400">–</span>
    <input type="time" value="{{ $end }}" @if($realInterval ?? false) name="intervals[{{ $dayIndex }}][{{ $intervalIndex }}][ends_at]" @endif data-time-end class="min-w-0 rounded border border-slate-200 px-2 py-1 text-sm" aria-label="Término do intervalo" required>
    <button type="button" data-remove-interval class="icon-button ml-auto" aria-label="Remover intervalo"><x-icon name="x" size="14" /></button>
</div>
