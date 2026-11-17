@props(['label', 'name', 'type' => 'text', 'value' => '', 'required' => false])
<label class="block text-sm font-medium text-slate-700">
    <span class="mb-1 block">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</span>
    <input {{ $attributes->merge(['class' => 'input-base']) }} type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" @required($required)>
</label>
