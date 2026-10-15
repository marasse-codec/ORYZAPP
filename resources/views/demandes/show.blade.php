@php
    use App\Domain\Acces\Enums\RoleEnum;
@endphp
@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Demande '.str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT))

@section('content')
<x-ui.page :titre="'Demande '.str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT)" class="max-w-4xl">
    <x-slot:actions>
        <x-ui.badge :ton="$demande->statut->ton()" class="px-3 py-1.5">{{ $demande->statut->libelle() }}</x-ui.badge>
        <x-ui.bouton :href="route('demandes.pdf', $demande->id_demande)" target="_blank">
            <span class="material-symbols-outlined text-base">picture_as_pdf</span> Imprimer la demande
        </x-ui.bouton>
    </x-slot:actions>

    {{-- Dépôt et calendrier souhaité --}}
    <x-ui.card>
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Déposée le</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $demande->date_demande?->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Début de mise en place souhaité</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $demande->periode_debut_mise_en_place?->format('d/m/Y') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Fin de mise en place espérée</dt>
                <dd class="mt-0.5 font-medium text-[--color-ink]">{{ $demande->periode_fin_mise_en_place?->format('d/m/Y') ?? '-' }}</dd>
            </div>
        </dl>

        @if ($demande->motifs_rejet && ($demande->sessionCommission?->estFinie() || auth()->user()->hasRole(RoleEnum::agentCommission)))
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">Motif de rejet</p>
                <p class="mt-1 text-sm text-red-700">{{ $demande->motifs_rejet }}</p>
            </div>
        @endif
    </x-ui.card>

    {{-- Demandeur --}}
    <x-ui.card titre="Demandeur">
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            @if ($demande->nom_structure)
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Nom de la structure</dt>
                    <dd class="mt-0.5 font-semibold text-[--color-ink]">{{ $demande->nom_structure }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Représentant / Nom</dt>
                <dd class="mt-0.5 text-[--color-ink]">{{ $demande->acteur?->nom }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Profil</dt>
                <dd class="mt-0.5 text-[--color-ink]">{{ $demande->typeActeur?->lib_type_acteur }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Téléphone</dt>
                <dd class="mt-0.5 text-[--color-ink]">{{ $demande->acteur?->telephone }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">E-mail</dt>
                <dd class="mt-0.5 break-all text-[--color-ink]">{{ $demande->acteur?->email ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-[--color-muted]">Localité(s) concernée(s)</dt>
                <dd class="mt-1.5 flex flex-wrap gap-1.5">
                    @forelse ($demande->localites as $localite)
                        <x-ui.badge>
                            {{ $localite->lib_localite }}
                            <span class="ml-1 font-normal text-slate-500">({{ $localite->departement?->lib_departement }})</span>
                        </x-ui.badge>
                    @empty
                        <span class="italic text-slate-400">Aucune localité renseignée</span>
                    @endforelse
                </dd>
            </div>
        </dl>
    </x-ui.card>

    {{-- Superficies par système de production --}}
    <x-ui.card titre="Systèmes de production" :sans-marge="$demande->systemesProductions->isNotEmpty()">
        @if ($demande->systemesProductions->isEmpty())
            <p class="text-sm text-[--color-muted]">Aucune superficie déclarée.</p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3">Système</th>
                        <th class="px-4 py-3 text-right">Superficie (ha)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($demande->systemesProductions as $systeme)
                        <tr>
                            <td class="px-4 py-3 text-[--color-ink]">{{ $systeme->typeSystemeProduction?->lib_type_systeme_production }}</td>
                            <td class="px-4 py-3 text-right text-[--color-ink]">{{ number_format($systeme->superficie, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td class="px-4 py-3 text-slate-700">Total</td>
                        <td class="px-4 py-3 text-right text-[--color-ink]">{{ number_format($demande->systemesProductions->sum('superficie'), 2, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </x-ui.tableau>
        @endif
    </x-ui.card>

    {{-- Pièces justificatives --}}
    <x-ui.card titre="Pièces justificatives">
        @if ($demande->piecesDemandes->isNotEmpty())
            <x-slot:entete>
                <x-ui.bouton :href="route('demandes.pieces', $demande->id_demande)" variante="secondaire" target="_blank" class="px-3 py-1.5 text-xs">
                    <span class="material-symbols-outlined text-sm">print</span> Imprimer les pièces
                </x-ui.bouton>
            </x-slot:entete>
        @endif

        @if ($demande->piecesDemandes->isEmpty())
            <p class="text-sm text-[--color-muted]">Aucune pièce jointe.</p>
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($demande->piecesDemandes as $piece)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-[--color-border] px-3 py-2">
                        <span class="text-[--color-ink]">{{ $piece->typePiece?->lib_type_piece }}</span>
                        <a href="{{ $piece->url() }}" target="_blank" rel="noopener" class="font-medium text-[--color-primary] hover:underline">Ouvrir</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    {{-- Types d'appuis sollicités --}}
    <x-ui.card titre="Types d'appuis sollicités" sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Type d'appui</th>
                    <th class="px-4 py-3">Service en charge</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @foreach ($demande->lignes as $ligne)
                    <tr>
                        <td class="px-4 py-3 font-medium text-[--color-ink]">{{ $ligne->typeAppui?->lib_type_appui }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $ligne->typeAppui?->service?->lib_service ?? 'Non assigné' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.tableau>
    </x-ui.card>

    {{-- Attributions accordées --}}
    @foreach ($demande->attributions as $attribution)
        <x-ui.card :titre="$loop->first ? 'Attributions accordées' : null" sans-marge>
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3">Appui</th>
                        <th class="px-4 py-3 text-right">Quantité</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($attribution->lignesAttributions as $ligne)
                        <tr>
                            <td class="px-4 py-3 text-[--color-ink]">{{ $ligne->appui?->libelle }}</td>
                            <td class="px-4 py-3 text-right text-[--color-ink]">{{ \App\Utils\Helper::quantite($ligne->quantite_attribuee) }}</td>
                            <td class="px-4 py-3 text-right text-[--color-ink]">{{ number_format($ligne->montant(), 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td colspan="2" class="px-4 py-3 text-slate-700">Total</td>
                        <td class="px-4 py-3 text-right text-[--color-ink]">{{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F</td>
                    </tr>
                </tfoot>
            </x-ui.tableau>
            @if ($attribution->commentaires)
                <p class="m-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $attribution->commentaires }}</p>
            @endif
        </x-ui.card>
    @endforeach

    {{-- Historique : tout ce qui est arrivé au dossier, d'après le journal des actions --}}
    @if ($chronologie->isNotEmpty())
        <x-ui.card titre="Chronologie du dossier" sans-marge>
            <ol class="divide-y divide-[--color-border]">
                @foreach ($chronologie as $evenement)
                    <li class="px-4 py-3 text-sm sm:px-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-medium text-[--color-ink]">{{ $evenement->actionEnum()?->libelle() ?? $evenement->action }}</span>
                            <span class="text-xs text-[--color-muted]">{{ $evenement->created_at->format('d/m/Y à H:i') }} · {{ $evenement->user?->name ?? 'Système' }}</span>
                        </div>
                        @if ($evenement->details)
                            <p class="mt-1 text-xs text-[--color-muted]">{{ $evenement->detailsLisibles() }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </x-ui.card>
    @endif

    <div>
        <x-ui.bouton :href="route('demandes.index')" variante="discret">← Retour à la liste des demandes</x-ui.bouton>
    </div>
</x-ui.page>
@endsection
