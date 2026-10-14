@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Mouvement de stock')

@section('content')
<x-ui.page :titre="'Mouvement MVT-'.str_pad($mouvement->id_mouvement_stock, 5, '0', STR_PAD_LEFT)" class="max-w-3xl">
    <x-slot:actions>
        <x-ui.badge>{{ $mouvement->typeMouvement?->libelle_type_mouvement ?? 'Type inconnu' }}</x-ui.badge>
        <x-ui.bouton :href="route('mouvements-stocks.index')" variante="secondaire">← Registre</x-ui.bouton>
    </x-slot:actions>

    <x-ui.card titre="Informations du mouvement">
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Date et heure</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $mouvement->date_mouvement?->translatedFormat('d F Y à H:i') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Quantité</dt>
                <dd class="mt-0.5 text-lg font-bold text-[--color-ink]">{{ $mouvement->quantite }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Appui</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $mouvement->appui?->libelle ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Équipement</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $mouvement->equipement?->matricule_equipement ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2 border-t border-[--color-border] pt-3">
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Enregistré par</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $mouvement->agent?->nom ?? 'Agent non renseigné' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    {{-- Un mouvement vient toujours d'un approvisionnement (entrée) ou d'une attribution (sortie, retour). --}}
    <x-ui.card titre="Origine de l'opération">
        @if ($mouvement->approvisionnement)
            <div class="space-y-1 text-sm">
                <p class="font-semibold text-[--color-ink]">
                    Approvisionnement N° {{ $mouvement->approvisionnement->id_approvisionnement }}
                    <span class="font-normal text-[--color-muted]">du {{ $mouvement->approvisionnement->date_approvisionnement?->format('d/m/Y') }}</span>
                </p>
                <p class="text-slate-600">Origine : <strong>{{ $mouvement->approvisionnement->origine ?? 'Non renseignée' }}</strong></p>
                @if ($mouvement->approvisionnement->reference_commande)
                    <p class="text-slate-600">Référence de commande : <strong>{{ $mouvement->approvisionnement->reference_commande }}</strong></p>
                @endif
            </div>
        @elseif ($mouvement->attribution)
            <div class="space-y-1 text-sm">
                <p class="flex flex-wrap items-center justify-between gap-2 font-semibold text-[--color-ink]">
                    <span>Attribution ATT-{{ str_pad($mouvement->attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}</span>
                    @if ($mouvement->attribution->demande)
                        <a href="{{ route('demandes.show', $mouvement->attribution->demande->id_demande) }}" class="text-xs font-semibold text-[--color-primary] hover:underline">Voir la demande</a>
                    @endif
                </p>
                <p class="text-slate-600">Bénéficiaire : <strong>{{ $mouvement->attribution->demande?->acteur?->nom ?? 'Inconnu' }}</strong></p>
            </div>
        @else
            <p class="text-sm italic text-[--color-muted]">Aucune pièce d'origine rattachée à ce mouvement.</p>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
