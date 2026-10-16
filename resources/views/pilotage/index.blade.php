@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Pilotage')

@php
    use App\Domain\Acces\Enums\PermissionEnum;
    // Une valeur avec son unité ; « - » seul quand il n'y a pas de donnée.
    $nombre = fn ($v, $d = 0, $unite = '') => $v === null ? '-' : trim(number_format($v, $d, ',', ' ').' '.$unite);
    // L'évolution par rapport à la campagne précédente, en petit sous le chiffre.
    $evolution = function (string $cle) use ($tableau) {
        $valeur = $tableau['evolutions'][$cle] ?? null;

        return $valeur === null ? null : ($valeur >= 0 ? '+' : '').number_format($valeur, 1, ',', ' ').' % vs cycle précédent';
    };
@endphp

@section('content')
<x-ui.page titre="Pilotage" sous-titre="La campagne en un coup d'œil : son activité, son évolution, et ce qui demande votre attention.">
    <x-slot:actions>
        {{-- On choisit l'année, puis l'un de ses cycles. --}}
        <form method="GET" action="{{ route('pilotage.index') }}" class="flex flex-wrap items-end gap-2">
            @include('statistiques.partials.choix-cycle')
        </form>
        @if ($campagne)
            @can(PermissionEnum::telechargerRapports->value)
                <x-ui.bouton :href="route('statistiques.rapport', ['campagne' => $campagne->id_campagne])">Rapport PDF</x-ui.bouton>
            @endcan
        @endif
    </x-slot:actions>

    @if (! $tableau)
        <x-ui.card><p class="text-sm text-[--color-muted]">Aucune campagne n'est enregistrée.</p></x-ui.card>
    @else
        @php [$i, $f] = [$tableau['indicateurs'], $tableau['finances']]; @endphp

        <p class="text-sm text-[--color-muted]">
            <strong>{{ $campagne->libelleComplet() }}</strong> ·
            {{ $campagne->estCloturee() ? 'cycle clôturé : chiffres définitifs.' : 'cycle en cours : chiffres provisoires.' }}
            @if ($tableau['precedente']) Comparé au cycle précédent : {{ $tableau['precedente']->libelleComplet() }}. @endif
        </p>

        {{-- Ce qui demande l'attention --}}
        @php $v = $tableau['vigilance']; @endphp
        <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            <x-ui.stat libelle="À arbitrer" :valeur="$v['a_arbitrer']" :ton="$v['a_arbitrer'] > 0 ? 'ambre' : 'vert'" />
            <x-ui.stat libelle="À publier" :valeur="$v['a_publier']" :ton="$v['a_publier'] > 0 ? 'ambre' : 'vert'" />
            <x-ui.stat libelle="Bons à retirer" :valeur="$v['a_retirer']" />
            <x-ui.stat libelle="Stocks sous le seuil" :valeur="$v['stocks_bas']" :ton="$v['stocks_bas'] > 0 ? 'rouge' : 'vert'" />
            <x-ui.stat libelle="Acteurs en impayé" :valeur="$v['acteurs_en_impaye']" :ton="$v['acteurs_en_impaye'] > 0 ? 'rouge' : 'vert'" />
        </section>

        {{-- Activité, avec l'évolution par rapport à la campagne précédente --}}
        <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <x-ui.stat libelle="Demandes soumises" :valeur="$i['soumises']" :indice="$evolution('soumises')" />
            <x-ui.stat libelle="Taux d'attribution" :valeur="$nombre($i['taux_attribution'], 1, '%')" :indice="$evolution('taux_attribution')" />
            <x-ui.stat libelle="Bénéficiaires servis" :valeur="$i['beneficiaires']" :indice="$evolution('beneficiaires')" />
            <x-ui.stat libelle="Superficie traitée" :valeur="$nombre($i['superficie_traitee'], 1, 'ha')" :indice="$evolution('superficie_traitee')" />
            <x-ui.stat libelle="Délai soumission → arbitrage" :valeur="$nombre($i['delai_arbitrage'], 1, 'j')" :indice="$evolution('delai_arbitrage')" />
            <x-ui.stat libelle="Délai arbitrage → retrait" :valeur="$nombre($i['delai_retrait'], 1, 'j')" />
            <x-ui.stat libelle="Montant dû" :valeur="$nombre($f['du'], 0, 'F')" :indice="$evolution('du')" />
            <x-ui.stat libelle="Taux de recouvrement" :valeur="$nombre($f['taux_recouvrement'], 1, '%')" :indice="$evolution('taux_recouvrement')" />
        </section>

        <div class="flex flex-wrap gap-2">
            @can(PermissionEnum::voirStatistiques->value)
                <x-ui.bouton :href="route('statistiques.index', ['campagne' => $campagne->id_campagne])" variante="secondaire">Détail des statistiques</x-ui.bouton>
                <x-ui.bouton :href="route('statistiques.comparaison')" variante="secondaire">Comparer les cycles</x-ui.bouton>
            @endcan
        </div>
    @endif
</x-ui.page>
@endsection
