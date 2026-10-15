@props(['hauteur' => 'h-14'])

{{-- Le logo de l'ADERIZ, déposé dans public/images/brand (aderiz_logo.png, .svg ou .webp).
     Tant qu'aucun fichier n'y est, le nom en lettres tient lieu de logo. --}}
@php
    $fichier = collect(['svg', 'png', 'webp', 'jpg'])
        ->map(fn ($extension) => "images/brand/aderiz_logo.$extension")
        ->first(fn ($chemin) => file_exists(public_path($chemin)));
@endphp

@if ($fichier)
    <img src="/{{ $fichier }}" alt="ADERIZ" {{ $attributes->class([$hauteur, 'mx-auto w-auto object-contain']) }}>
@else
    <span {{ $attributes->class(['text-xl font-black tracking-[0.3em] text-[--color-primary]']) }}>ADERIZ</span>
@endif
