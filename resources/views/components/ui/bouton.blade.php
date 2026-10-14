@props(['href' => null, 'variante' => 'primaire', 'type' => 'button'])

@php
    $classes = [
        'primaire' => 'bg-[--color-primary] text-white shadow-sm hover:bg-[--color-primary-dark]',
        'secondaire' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'danger' => 'border border-red-300 bg-red-50 text-red-700 hover:bg-red-100',
        'discret' => 'text-[--color-primary] hover:bg-[--color-primary-soft]',
    ][$variante] ?? '';
    $base = 'inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[--color-primary] disabled:cursor-not-allowed disabled:opacity-50';
@endphp

{{-- Un bouton ou, avec « href », un lien qui lui ressemble. Variantes : primaire, secondaire, danger, discret. --}}
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$base, $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$base, $classes]) }}>{{ $slot }}</button>
@endif
