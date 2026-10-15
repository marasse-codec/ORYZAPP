{{--
    Bon d'enlèvement / livraison, sur le modèle papier de l'ADERIZ : en-tête
    républicain, deux volets (enlèvement au magasin, livraison au bénéficiaire)
    et trois émargements. C'est la pièce qui vaut titre de retrait.
--}}
@php
    $dateBon = $attribution->date_destockage ?? $attribution->session?->date_debut_session ?? now();
    $numero = str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT).'/'.$dateBon->format('m/Y');
    $localites = $attribution->demande?->localites->pluck('lib_localite')->implode(', ');
    $lignes = $attribution->lignesAttributions;
@endphp
@extends('layouts.impression', ['retour' => route('demandes.show', $attribution->id_demande)])

@section('titre', "Bon d'enlèvement / livraison")
@section('sous-titre', 'N° '.$numero)

@section('entete')
    <header class="insecable text-center text-[11px] uppercase leading-snug">
        <div class="flex items-start justify-between gap-6">
            <div class="w-1/2 text-center">
                <p class="font-semibold">Ministère de l'Agriculture, du Développement Rural<br>et des Productions Vivrières</p>
                <p class="mt-1">Ministère délégué en charge des<br>Productions Vivrières</p>
                <p class="mt-1">Agence Nationale de Développement de la Filière Riz</p>
                <x-ui.logo-aderiz class="mt-3 block" />
                <p class="text-[10px] font-semibold">DAP/SM/BM</p>
            </div>
            <div class="w-1/2 text-center">
                <p class="font-semibold">République de Côte d'Ivoire</p>
                <p class="normal-case">Union - Discipline - Travail</p>
            </div>
        </div>
    </header>

    <h1 class="insecable mt-6 border-2 border-slate-800 py-2 text-center text-base font-bold uppercase">
        Bon d'enlèvement / livraison : N° {{ $numero }}
    </h1>
@endsection

@section('document')

    <section class="mt-4 grid grid-cols-2 gap-6 text-sm font-semibold uppercase">
        <div>
            <p>Lieu d'enlèvement : ADERIZ Yamoussoukro</p>
            <p class="mt-1">Bénéficiaire : {{ $attribution->demande?->acteur?->nom }}</p>
        </div>
        <p>Lieu de livraison : {{ $localites ?: '-' }}</p>
    </section>

    <section class="mt-4 insecable grid grid-cols-2 border border-slate-800 text-sm">
        @foreach ([['Enlèvement', 'Quantité enlevée'], ['Livraison', 'Quantité livrée']] as [$volet, $colonne])
            <div class="{{ $loop->first ? 'border-r border-slate-800' : '' }}">
                <p class="border-b border-slate-800 py-1 text-center font-bold uppercase">{{ $volet }}</p>
                <div class="grid grid-cols-[1fr_auto] border-b border-slate-800 text-center font-semibold">
                    <p class="border-r border-slate-800 px-2 py-1">Désignation</p>
                    <p class="px-2 py-1">{{ $colonne }}</p>
                </div>
                @foreach ($lignes as $ligne)
                    <div class="grid grid-cols-[1fr_auto] border-b border-slate-300 font-semibold uppercase">
                        <p class="border-r border-slate-800 px-2 py-1">{{ $ligne->appui?->libelle }}</p>
                        <p class="min-w-20 px-2 py-1 text-center">
                            @if ($loop->parent->first)
                                {{ \App\Utils\Helper::quantite($ligne->quantite_attribuee) }}
                            @endif
                        </p>
                    </div>
                @endforeach
                <p class="min-h-24 px-2 py-1">
                    <span class="font-semibold">Observations :</span>
                    @if ($loop->first)
                        <span class="text-xs">relever pour chaque engin le numéro de châssis</span>
                        @if ($attribution->commentaires)
                            <br>{{ $attribution->commentaires }}
                        @endif
                    @endif
                </p>
            </div>
        @endforeach
    </section>

    <section class="mt-6 insecable grid grid-cols-3 gap-6 text-xs">
        <div>
            <p class="font-semibold underline">Pour l'ADERIZ (Chef de Service Mécanisation)</p>
            <p class="text-[10px] italic text-slate-500">Nom et prénoms, date, signature, cachet</p>
            <div class="mt-2 h-28"></div>
        </div>
        <div>
            <p class="font-semibold underline">Pour le Service Moyens Généraux</p>
            <p class="text-[10px] italic text-slate-500">Nom et prénoms, date, signature</p>
            <div class="mt-2 h-28"></div>
        </div>
        <div>
            <p class="font-semibold underline">Pour le bénéficiaire</p>
            <p class="text-[10px] italic text-slate-500">Nom et prénoms, date, signature, cachet</p>
            <div class="mt-2 h-28"></div>
        </div>
    </section>

@endsection
