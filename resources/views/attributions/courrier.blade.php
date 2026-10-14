{{--
    Courrier de notification adressé au demandeur une fois son attribution
    publiée, sur le modèle papier de la Direction Appui à la Production.
--}}
@php
    $demande = $attribution->demande;
    $beneficiaire = $demande?->nom_structure ?: ($demande?->acteur?->nom ?? 'Bénéficiaire');
    $localites = $demande?->localites->pluck('lib_localite')->implode(', ');
    $lignes = $attribution->lignesAttributions;
    $total = $attribution->montantTotal();
    $lettres = class_exists(\NumberFormatter::class)
        ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format((int) round($total))
        : null;
    $date = $attribution->session?->date_debut_session ?? now();
@endphp
@extends('layouts.impression', ['retour' => route('attributions.show', $attribution->id_attribution)])

@section('titre', 'Courrier d\'attribution')
@section('sous-titre', 'ATT-'.str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT))

@section('entete')
    <header class="insecable text-[11px] font-semibold uppercase leading-snug">
        <div class="flex items-start justify-between gap-6">
            <div class="w-1/2 text-center">
                <p>Ministère de l'Agriculture, du Développement Rural<br>et des Productions Vivrières</p>
                <p class="mt-2">Agence pour le Développement de la Filière Riz</p>
                <p class="mt-2 text-xl font-black tracking-[0.3em] text-[--color-primary]">ADERIZ</p>
                <p class="mt-1">Direction Appui à la Production</p>
            </div>
            <div class="w-1/2 text-center">
                <p>République de Côte d'Ivoire</p>
                <p class="mt-1 text-[10px] font-normal normal-case italic">Union - Discipline - Travail</p>
            </div>
        </div>
        <div class="mt-5 flex items-end justify-between font-normal normal-case">
            <p>Réf. N° ........../MINADERPV/ADERIZ/DAP/KB</p>
            <p>Abidjan, le {{ $date->format('d/m/Y') }}</p>
        </div>
    </header>
@endsection

@section('document')

    <section class="mt-6 ml-[50%] text-sm">
        <p>À</p>
        <p class="mt-2 font-semibold">{{ $beneficiaire }}</p>
        @if ($demande?->nom_structure && $demande?->acteur?->nom)
            <p>{{ $demande->acteur->nom }}</p>
        @endif
        @if ($localites)
            <p class="mt-1 font-semibold underline">{{ $localites }}</p>
        @endif
    </section>

    <p class="mt-6 text-sm"><span class="underline">Objet</span> : V/Demande d'intrants</p>

    <section class="mt-6 space-y-4 text-sm leading-relaxed text-justify">
        <p>Monsieur,</p>

        <p>
            Par correspondance adressée au Directeur Général de l'ADERIZ, vous avez sollicité des appuis
            pour la mise en valeur de vos exploitations.
        </p>

        <p>
            Je viens par la présente vous informer qu'un accompagnement est mis à votre disposition
            selon les termes suivants :
        </p>

        <ul class="space-y-1 pl-6">
            @foreach ($lignes as $ligne)
                @php($montant = $ligne->montant())
                <li class="list-['-'] pl-3">
                    <strong>{{ \App\Utils\Helper::quantite($ligne->quantite_attribuee) }}
                    {{ $ligne->appui?->uniteDosage?->lib_unite }}</strong>
                    de {{ $ligne->appui?->libelle }}
                    d'une valeur de <strong>{{ number_format($montant, 0, ',', ' ') }} francs CFA</strong>{{ $loop->last ? '.' : ' ;' }}
                </li>
            @endforeach
        </ul>

        @if ($lignes->count() > 1)
            <p>
                Soit un total de <strong>{{ $lettres ? $lettres.' ('.number_format($total, 0, ',', ' ').')' : number_format($total, 0, ',', ' ') }}
                francs CFA</strong>.
            </p>
        @endif

        <p>
            Dans un souci de pérennisation des appuis, les intrants mis à votre disposition seront remboursés
            au terme du cycle de production. En outre, vous disposez d'un délai de <strong>trente (30) jours</strong>
            à compter de la date de signature du présent courrier pour procéder à l'enlèvement des intrants au
            centre de traitement et de conditionnement de semences de Yamoussoukro, sur présentation du bon
            d'attribution et d'une pièce d'identité.
        </p>

        <p>
            Aussi, en récupérant les appuis mis à votre disposition, vous vous engagez à vous soumettre au
            dispositif de suivi-évaluation de l'ADERIZ et à fournir des rapports mensuels selon le canevas
            joint au présent courrier.
        </p>

        <p>Je vous prie d'agréer, <strong>Monsieur</strong>, l'assurance de ma considération distinguée.</p>
    </section>

    <section class="insecable mt-8 ml-[50%] text-center text-sm font-semibold">
        <p>Le Directeur de l'Appui à la Production</p>
        <div class="h-28"></div>
    </section>

@endsection
