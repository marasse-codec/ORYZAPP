{{--
    Les options du choix de campagne, rangées par année : on peut viser un
    cycle de l'année en cours comme un cycle de l'année à venir (préparation
    de la saison suivante). Chaque groupe dit où se situe l'année.
    Paramètres : $campagnes (campagnes ouvertes), $selection (id de la campagne choisie).
--}}
@props(['campagnes', 'selection' => null])

@foreach ($campagnes->sortBy('mois_debut_campagne')->groupBy(fn ($c) => $c->annee()) as $annee => $cycles)
    @php
        $situation = match (true) {
            $annee > today()->year => 'année à venir',
            $annee === today()->year => 'année en cours',
            default => 'année passée',
        };
    @endphp
    <optgroup label="{{ $annee }} · {{ $situation }}">
        @foreach ($cycles as $campagne)
            <option value="{{ $campagne->id_campagne }}" @selected((string) $selection === (string) $campagne->id_campagne)>
                {{ $campagne->libelleComplet() }} : {{ $campagne->mois_debut_campagne?->format('m/Y') }} - {{ $campagne->mois_fin_campagne?->format('m/Y') }}{{ $campagne->estAVenir() ? ' · à venir' : '' }}
            </option>
        @endforeach
    </optgroup>
@endforeach
