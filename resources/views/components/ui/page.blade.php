@props(['titre', 'sousTitre' => null])

{{--
    Le gabarit d'une page : titre, sous-titre, actions à droite (slot « actions »), puis le contenu.
    Les actions passent à la ligne sur téléphone.
--}}
<div {{ $attributes->class('mx-auto w-full max-w-7xl space-y-6') }}>
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-[--color-ink] sm:text-2xl">{{ $titre }}</h1>
            @if ($sousTitre)
                <p class="mt-1 text-sm text-[--color-muted]">{{ $sousTitre }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </header>

    {{ $slot }}
</div>
