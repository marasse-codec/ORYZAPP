@props(['titre' => null, 'sansMarge' => false])

{{-- Une carte : fond blanc, bord fin. Un titre et un en-tête d'actions (slot « entete ») sont facultatifs. --}}
<section {{ $attributes->class('rounded-xl border border-[--color-border] bg-white shadow-sm') }}>
    @if ($titre || isset($entete))
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[--color-border] px-4 py-3 sm:px-6">
            @if ($titre)
                <h2 class="text-base font-semibold text-[--color-ink]">{{ $titre }}</h2>
            @endif
            @isset($entete)
                <div class="flex flex-wrap items-center gap-2">{{ $entete }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-4 sm:p-6' => ! $sansMarge])>
        {{ $slot }}
    </div>
</section>
