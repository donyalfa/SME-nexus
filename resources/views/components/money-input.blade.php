@props(['name', 'label', 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input type="number" step="0.01" min="0" inputmode="decimal" required
           id="{{ $name }}" name="{{ $name }}" x-model="f.{{ $name }}"
           @error($name) aria-invalid="true" @enderror
           class="mt-1 block w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 {{ $errors->has($name) ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-teal-600 focus:ring-teal-600/25' }}">
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
