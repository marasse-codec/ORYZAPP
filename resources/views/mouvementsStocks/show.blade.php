@extends('layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- En-tête / Titre --}}
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('mouvements-stocks.index') }}" class="text-xs font-semibold text-[--color-primary] hover:underline flex items-center gap-1">
                    ← Retour à la liste des mouvements
                </a>
                <h1 class="text-2xl font-bold text-slate-800 mt-1">
                    Mouvement N° MVT-{{ str_pad($mouvement->id_mouvement_stock, 5, '0', STR_PAD_LEFT) }}
                </h1>
            </div>
            <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                {{ $mouvement->typeMouvement?->libelle ?? 'Type inconnu' }}
            </span>
        </div>

        {{-- Carte Principale : Informations Générales --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-[--color-primary] mb-4">Informations du mouvement</h2>
            
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Date & Heure du mouvement</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">
                        {{ $mouvement->date_mouvement ? \Carbon\Carbon::parse($mouvement->date_mouvement)->translatedFormat('d F Y à H:i') : '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Quantité impactée</dt>
                    <dd class="mt-0.5 font-bold text-lg text-slate-900">
                        {{ $mouvement->quantite }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Appui / Intrant</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">
                        {{ $mouvement->appui?->libelle ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Équipement</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">
                        {{ $mouvement->equipement?->libelle ?? '—' }}
                    </dd>
                </div>

                <div class="sm:col-span-2 border-t border-slate-100 pt-3">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Agent Saisie / Enregistreur</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">
                        {{ $mouvement->agent ? $mouvement->agent->nom . ' ' . $mouvement->agent->prenom : 'Agent non spécifié' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Origine de l'Opération (Approvisionnement OU Attribution) --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-[--color-primary] mb-4">Origine de l'opération</h2>

            @if($mouvement->approvisionnement)
                {{-- Cas 1 : Mouvement issu d'un Entrée / Approvisionnement --}}
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-slate-800">Approvisionnement N° {{ $mouvement->approvisionnement->id_approvisionnement }}</span>
                        <span class="text-xs text-slate-500">{{ $mouvement->approvisionnement->date_approvisionnement?->format('d/m/Y') }}</span>
                    </div>
                    <p class="text-slate-600">
                        Fournisseur / Source : <strong>{{ $mouvement->approvisionnement->fournisseur ?? 'N/A' }}</strong>
                    </p>
                </div>

            @elseif($mouvement->ligneAttribution)
                {{-- Cas 2 : Mouvement issu d'une Sortie / Déstockage lié à une attribution --}}
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-slate-800">Attribution N° {{ $mouvement->ligneAttribution->id_attribution }}</span>
                        @if(optional($mouvement->ligneAttribution->attribution->demande)->id_demande)
                            <a href="{{ route('demandes.show', $mouvement->ligneAttribution->attribution->demande->id_demande) }}" 
                               class="text-xs font-semibold text-[--color-primary] hover:underline">
                                Voir la demande
                            </a>
                        @endif
                    </div>
                    <p class="text-slate-600">
                        Bénéficiaire : 
                        <strong>{{ $mouvement->ligneAttribution->attribution->demande->acteur?->nom ?? 'Inconnu' }}</strong>
                    </p>
                </div>

            @else
                {{-- Autre opération (Ajustement manuel, Perte, etc.) --}}
                <p class="text-sm text-slate-500 italic">
                    Aucune pièce d'origine directe (Approvisionnement ou Attribution) rattachée à ce mouvement (Ajustement direct).
                </p>
            @endif
        </div>

    </div>
</div>
@endsection