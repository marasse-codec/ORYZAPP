{{-- Caisse : ce qui est dû, encaissé, et les acteurs qui n'ont pas fini de régler (bloqués pour toute nouvelle demande) --}}
@php $synthese = $reglements['synthese']; @endphp
<section class="space-y-3">
    <h2 class="text-base font-semibold text-[--color-ink]">Règlements</h2>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-ui.stat libelle="Montant dû" :valeur="number_format($synthese['du'], 0, ',', ' ').' F'" />
        <x-ui.stat libelle="Encaissé" ton="vert" :valeur="number_format($synthese['paye'], 0, ',', ' ').' F'"
                   :indice="$synthese['taux'] !== null ? number_format($synthese['taux'], 1, ',', ' ').' % du dû' : null" />
        <x-ui.stat libelle="Reste à encaisser" :ton="$synthese['reste'] > 0 ? 'rouge' : 'vert'" :valeur="number_format($synthese['reste'], 0, ',', ' ').' F'"
                   :indice="$synthese['nb_attributions'].' attribution(s) non soldée(s)'" />
        <x-ui.stat libelle="Acteurs bloqués" :ton="$synthese['nb_acteurs'] > 0 ? 'ambre' : 'vert'" :valeur="$synthese['nb_acteurs']"
                   indice="Aucune nouvelle demande possible" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card titre="Acteurs en impayé" sans-marge>
            <ul class="divide-y divide-[--color-border]">
                @forelse ($reglements['acteurs'] as $ligne)
                    <li>
                        <a href="{{ route('attributions.show', $ligne['attributions']->first()->id_attribution) }}"
                           class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-3 text-sm hover:bg-slate-50 sm:px-6">
                            <span class="min-w-0 truncate font-medium text-[--color-ink]">{{ $ligne['acteur']->nom }}
                                <span class="text-xs font-normal text-[--color-muted]">· {{ $ligne['attributions']->count() }} attribution(s)</span>
                            </span>
                            <x-ui.badge ton="rouge">{{ number_format($ligne['reste_du'], 0, ',', ' ') }} F</x-ui.badge>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-[--color-muted] sm:px-6">Aucun acteur en impayé.</li>
                @endforelse
            </ul>
        </x-ui.card>

        <x-ui.card titre="Attributions non soldées" sans-marge>
            <ul class="divide-y divide-[--color-border]">
                @forelse ($reglements['attributions'] as $attribution)
                    <li>
                        <a href="{{ route('attributions.show', $attribution->id_attribution) }}"
                           class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-3 text-sm hover:bg-slate-50 sm:px-6">
                            <span class="min-w-0 truncate text-[--color-ink]">
                                <span class="font-medium">N° {{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}</span>
                                · {{ $attribution->demande?->acteur?->nom ?? 'Bénéficiaire' }}
                                <span class="text-xs text-[--color-muted]">· publiée il y a {{ (int) $attribution->date_publication->diffInDays(now()) }} j</span>
                            </span>
                            <span class="text-xs text-[--color-muted]">
                                {{ number_format($attribution->totalPaye(), 0, ',', ' ') }} / {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-[--color-muted] sm:px-6">Toutes les attributions publiées sont soldées.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</section>
