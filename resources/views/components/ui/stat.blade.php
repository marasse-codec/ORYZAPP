@props(['libelle', 'valeur', 'indice' => null, 'ton' => 'neutre', 'href' => null])

@php
    $teinte = [
        'neutre' => 'text-[--color-ink]',
        'vert' => 'text-emerald-700',
        'ambre' => 'text-amber-600',
        'rouge' => 'text-rose-600',
    ][$ton] ?? 'text-[--color-ink]';
    $carte = 'block rounded-xl border border-[--color-border] bg-white p-4 shadow-sm sm:p-5';
@endphp

{{-- Un chiffre clé : libellé, valeur, indice. Cliquable si un lien est donné. --}}
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$carte, 'transition hover:border-[--color-primary] hover:shadow']) }}>
        <p class="text-xs font-medium uppercase tracking-wide text-[--color-muted]">{{ $libelle }}</p>
        <p class="mt-2 text-2xl font-bold sm:text-3xl {{ $teinte }}">{{ $valeur }}</p>
        @if ($indice)<p class="mt-1 text-xs text-[--color-muted]">{{ $indice }}</p>@endif
    </a>
@else
    <div {{ $attributes->class([$carte]) }}>
        <p class="text-xs font-medium uppercase tracking-wide text-[--color-muted]">{{ $libelle }}</p>
        <p class="mt-2 text-2xl font-bold sm:text-3xl {{ $teinte }}">{{ $valeur }}</p>
        @if ($indice)<p class="mt-1 text-xs text-[--color-muted]">{{ $indice }}</p>@endif
    </div>
@endif
