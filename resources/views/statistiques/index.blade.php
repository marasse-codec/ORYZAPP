@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Statistiques')

@section('content')
<x-ui.page titre="Statistiques" sous-titre="Les chiffres d'une campagne, pour décider. Population : les demandes soumises de la campagne (et des filtres).">
    @if ($campagne)
        <x-slot:actions>
            <x-ui.bouton :href="route('statistiques.comparaison', $filtre->parametres())" variante="secondaire">Comparer les campagnes</x-ui.bouton>
            @can(\App\Domain\Acces\Enums\PermissionEnum::telechargerRapports->value)
                <x-ui.bouton :href="route('statistiques.rapport', ['campagne' => $campagne->id_campagne] + $filtre->parametres())">Rapport PDF</x-ui.bouton>
            @endcan
        </x-slot:actions>
    @endif

    @include('statistiques.partials.filtres', ['action' => route('statistiques.index'), 'filtre' => $filtre ?? null, 'avecCampagne' => true])

    @if (! $campagne)
        <x-ui.card><p class="text-sm text-[--color-muted]">Aucune campagne n'est enregistrée.</p></x-ui.card>
    @else
        @if ($campagne->estCloturee())
            <p class="rounded-lg bg-slate-100 px-4 py-2 text-sm text-slate-700">Campagne clôturée le {{ $campagne->cloturee_le->format('d/m/Y') }} : ces chiffres sont définitifs.</p>
        @endif
        @unless ($filtre->estVide())
            <p class="rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-800">Chiffres restreints par les filtres ci-dessus : ils ne portent pas sur toute la campagne.</p>
        @endunless

        {{-- Chiffres clés --}}
        <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            <x-ui.stat libelle="Demandes soumises" :valeur="$indicateurs['soumises']" />
            <x-ui.stat libelle="Attribuées" ton="vert"
                       :valeur="$indicateurs['attribuees']"
                       :indice="$indicateurs['taux_attribution'] !== null ? number_format($indicateurs['taux_attribution'], 1, ',', ' ').' % des demandes soumises' : null" />
            <x-ui.stat libelle="Rejetées" ton="rouge" :valeur="$indicateurs['rejetees']" />
            <x-ui.stat libelle="Superficie concernée"
                       :valeur="number_format($indicateurs['superficie_concernee'], 2, ',', ' ').' ha'" />
            <x-ui.stat libelle="Superficie traitée" ton="vert"
                       :valeur="number_format($indicateurs['superficie_traitee'], 2, ',', ' ').' ha'"
                       :indice="$indicateurs['taux_traitement'] !== null ? number_format($indicateurs['taux_traitement'], 1, ',', ' ').' % de la superficie concernée' : null" />
            <x-ui.stat libelle="Bénéficiaires servis" :valeur="$indicateurs['beneficiaires']" />
            <x-ui.stat libelle="Délai soumission → arbitrage"
                       :valeur="$indicateurs['delai_arbitrage'] !== null ? number_format($indicateurs['delai_arbitrage'], 1, ',', ' ').' j' : '-'" />
            <x-ui.stat libelle="Délai arbitrage → retrait"
                       :valeur="$indicateurs['delai_retrait'] !== null ? number_format($indicateurs['delai_retrait'], 1, ',', ' ').' j' : '-'" />
        </section>

        {{-- L'argent de la campagne --}}
        @include('statistiques.partials.finances')

        {{-- Graphiques --}}
        <section class="grid gap-4 lg:grid-cols-2">
            <x-ui.card titre="Demandes par statut">
                <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueStatuts) }}"></canvas></div>
            </x-ui.card>

            <x-ui.card titre="Bénéficiaires par type d'acteur">
                @if ($graphiqueTypesActeurs)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueTypesActeurs) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Aucune attribution sur cette campagne.</p>
                @endif
            </x-ui.card>

            <x-ui.card titre="Superficies par écologie (ha)">
                @if ($graphiqueSuperficies)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueSuperficies) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Aucune superficie sur cette campagne.</p>
                @endif
            </x-ui.card>

            <x-ui.card titre="Rendement moyen par écologie (t/ha)">
                @if ($graphiqueRendements)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueRendements) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Aucun rendement transmis par les DP pour cette campagne.</p>
                @endif
                <ul class="mt-3 space-y-1 text-xs text-[--color-muted]">
                    @foreach ($rendements as $ecologie => $r)
                        <li>{{ $ecologie }} : {{ $r['renseignees'] }}/{{ $r['declarees'] }} superficie(s) renseignée(s)@if ($r['rendement'] !== null), {{ number_format($r['production'], 1, ',', ' ') }} t sur {{ number_format($r['recoltee'], 1, ',', ' ') }} ha @endif</li>
                    @endforeach
                </ul>
            </x-ui.card>

            <x-ui.card titre="Types d'appuis : demandés et servis">
                @if ($graphiqueTypesAppuis)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueTypesAppuis) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Aucune demande soumise sur cette campagne.</p>
                @endif
            </x-ui.card>

            <x-ui.card titre="Localités les plus demandeuses">
                @if ($graphiqueLocalites)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueLocalites) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Aucune demande soumise sur cette campagne.</p>
                @endif
            </x-ui.card>
        </section>

        {{-- Satisfaction des quantités, appui par appui --}}
        <x-ui.card titre="Satisfaction des quantités demandées" sans-marge>
            @if ($satisfaction->isEmpty())
                <p class="p-4 text-sm text-[--color-muted] sm:p-6">Aucune ligne de demande sur cette campagne.</p>
            @else
                <x-ui.tableau>
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                        <tr>
                            <th class="px-4 py-3 sm:px-6">Appui</th>
                            <th class="px-4 py-3 text-right">Demandé</th>
                            <th class="px-4 py-3 text-right">Attribué</th>
                            <th class="px-4 py-3 text-right sm:px-6">Taux</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[--color-border]">
                        @foreach ($satisfaction as $ligne)
                            <tr>
                                <td class="px-4 py-3 text-[--color-ink] sm:px-6">{{ $ligne['appui'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($ligne['demandee'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($ligne['attribuee'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right font-semibold sm:px-6">{{ $ligne['taux'] !== null ? number_format($ligne['taux'], 1, ',', ' ').' %' : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.tableau>
            @endif
        </x-ui.card>
    @endif
</x-ui.page>
@endsection

@push('scripts')
    @vite('resources/js/charts.js')
@endpush
