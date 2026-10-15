{{-- Objectifs de la campagne comparés au réalisé ($objectifs : array<Objectif>). Rien n'est affiché sans objectif fixé. --}}
@if (! empty($objectifs))
    <x-ui.card titre="Objectifs de la campagne" sans-marge>
        <ul class="divide-y divide-[--color-border]">
            @foreach ($objectifs as $objectif)
                @php
                    $format = fn ($v) => number_format($v, 1, ',', ' ').' '.$objectif->unite;
                    $etat = match ($objectif->atteint()) { true => 'Atteint', false => 'Non atteint', default => 'Pas encore mesurable' };
                @endphp
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm sm:px-6">
                    <span class="font-medium text-[--color-ink]">{{ $objectif->libelle }}</span>
                    <span class="text-[--color-muted]">
                        Cible : {{ $objectif->auMoins ? 'au moins' : 'au plus' }} {{ $format($objectif->cible) }}
                        · Réalisé : {{ $objectif->realise !== null ? $format($objectif->realise) : '-' }}
                    </span>
                    <x-ui.badge :ton="$objectif->ton() === 'gris' ? 'neutre' : $objectif->ton()">{{ $etat }}</x-ui.badge>
                </li>
            @endforeach
        </ul>
    </x-ui.card>
@endif
