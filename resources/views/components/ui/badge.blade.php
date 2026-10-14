@props(['ton' => 'neutre'])

@php
    $classes = [
        'neutre' => 'bg-slate-100 text-slate-700',
        'vert' => 'bg-emerald-100 text-emerald-800',
        'ambre' => 'bg-amber-100 text-amber-800',
        'rouge' => 'bg-rose-100 text-rose-800',
        'bleu' => 'bg-sky-100 text-sky-800',
    ][$ton] ?? 'bg-slate-100 text-slate-700';
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', $classes]) }}>{{ $slot }}</span>
