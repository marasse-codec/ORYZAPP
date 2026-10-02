@extends($layout ?? 'layouts.app')

@php
    use Illuminate\Support\Carbon;
    Carbon::setLocale('fr');

    $demande = $attribution->demande;
    $nomEntite = $demande?->nom_structure ?: ($demande?->acteur?->nom ?? 'Bénéficiaire');
    $localitesStr = $demande?->localites->pluck('lib_localite')->join(', ');
@endphp

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- En-tête avec statut et actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-sm gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-[--color-primary]">
                        Attribution {{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }} <br>
                        Demande {{str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                    </h1>
                    {{-- Badge de statut dynamique basé sur l'Enum --}}
                    @if($attribution->id_statut_attribution)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $attribution->id_statut_attribution->couleur() }}">
                            {{ $attribution->id_statut_attribution->libelle() }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-[--color-text-secondary] mt-1">
                    Bénéficiaire : <span class="font-semibold text-[--color-primary]">{{ $nomEntite }}</span>
                    @if ($demande?->nom_structure && $demande?->acteur?->nom)
                        <span class="text-xs text-slate-500">(Rep : {{ $demande->acteur->nom }})</span>
                    @endif
                    · {{ $demande?->typeActeur?->lib_type_acteur }}
                    · Localité(s) : {{ $localitesStr ?: 'Non spécifiée' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('demandes.show', $demande->id_demande) }}"
                   class="inline-flex items-center gap-1 rounded-md border border-[--color-primary] bg-emerald-50 px-3 py-2 text-sm font-medium text-[--color-primary] hover:bg-emerald-100 shadow-sm transition-colors">
                    Consulter dossier
                </a>

                {{-- Boutons d'action conditionnels pour les commissions (Valider / Annuler) --}}
                @if(auth()->user()->can(\App\Enums\PermissionEnum::gererCommission->value) && $sessionOuverte)
                    <form action="{{ route('attributions.valider') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="id_attribution" value="{{ $attribution->id_attribution }}">
                        <button type="submit" 
                                class="inline-flex items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100 shadow-sm"
                                onclick="return confirm('Voulez-vous confirmer la validation de cette attribution ? Elle sera traitée au nom de la séance de commission du {{ Carbon::parse($sessionOuverte->date_debut_session)->translatedFormat('l d F Y') }}.')">
                            Valider
                        </button>
                    </form>

                    <form action="{{ route('attributions.annuler') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="id_attribution" value="{{ $attribution->id_attribution }}">
                        <button type="submit" 
                                class="inline-flex items-center gap-1 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-[--color-accent] hover:bg-red-100 shadow-sm"
                                onclick="return confirm('Voulez-vous confirmer l\'annulation de cette attribution ?')">
                            Annuler
                        </button>
                    </form>
                @endif
                
                @if($attribution->session && $estValidee)
                    <a href="{{ route('attributions.bon', $attribution->id_attribution) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm">
                        <span class="material-symbols-outlined text-base">print</span> Imprimer le bon
                    </a>
                @endif

                <a href="{{ url()->previous() }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

        {{-- Tableau récapitulatif des lignes attribuées --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-base font-semibold text-[--color-primary]">Détails des appuis attribués</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-[--color-text-secondary] uppercase tracking-wider">
                            <th class="py-3 px-4">Appui</th>
                            <th class="py-3 px-4 text-center">Quantité</th>
                            <th class="py-3 px-4 text-right">Coût unitaire</th>
                            <th class="py-3 px-4 text-center">Remise</th>
                            <th class="py-3 px-4 text-right">Montant total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-[--color-text-primary]">
                        @forelse ($attribution->lignesAttributions as $ligne)
                            <tr>
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    {{ $ligne->appui?->libelle }}
                                    @if($ligne->appui?->uniteAppui)
                                        <span class="text-xs text-[--color-text-secondary]">({{ $ligne->appui->uniteAppui->libelle }})</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-semibold">
                                    {{ $ligne->quantite_attribuee }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    {{ number_format($ligne->cout_attribution, 0, ',', ' ') }} F
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($ligne->remise > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-[--color-primary]">
                                            -{{ $ligne->remise }}%
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-slate-900">
                                    {{ number_format($ligne->montant(), 0, ',', ' ') }} F
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-[--color-text-secondary]">Aucune ligne enregistrée pour cette attribution.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Total général --}}
            <div class="flex justify-end border-t border-slate-200 pt-4">
                <div class="text-right">
                    <p class="text-sm text-[--color-text-secondary]">Montant total global :</p>
                    <p class="text-xl font-bold text-[--color-primary]">
                        {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F
                    </p>
                </div>
            </div>
        </div>

        {{-- Observations & Commentaires --}}
        @if($attribution->commentaires)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-2">
                <h2 class="text-base font-semibold text-[--color-primary]">Observations &amp; motivations</h2>
                <p class="text-sm text-[--color-text-primary] bg-slate-50 p-4 rounded-lg border border-slate-100">
                    {{ $attribution->commentaires }}
                </p>
            </div>
        @endif

        {{-- Informations de déstockage (si effectué) --}}
        @if($attribution->date_destockage)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm space-y-3">
                <h2 class="text-base font-semibold text-[--color-primary]">Informations de sortie de stock</h2>
                <div class="grid gap-4 sm:grid-cols-3 text-sm text-slate-800">
                    <div>
                        <span class="block text-xs text-[--color-text-secondary] font-medium">Date de déstockage</span>
                        {{ $attribution->date_destockage->format('d/m/Y') }}
                    </div>
                    <div>
                        <span class="block text-xs text-[--color-text-secondary] font-medium">Récipiendaire</span>
                        {{ $attribution->recipiendaire }}
                    </div>
                    <div>
                        <span class="block text-xs text-[--color-text-secondary] font-medium">Pièce d'identité</span>
                        {{ $attribution->numero_piece_identite ?? 'N/A' }}
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection