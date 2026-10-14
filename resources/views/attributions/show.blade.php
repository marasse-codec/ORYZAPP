@use('App\Domain\Acces\Enums\PermissionEnum')

@extends($layout ?? 'layouts.aderiz')

@php
    $demande = $attribution->demande;
    $nomEntite = $demande?->nom_structure ?: ($demande?->acteur?->nom ?? 'Bénéficiaire');
    $localitesStr = $demande?->localites->pluck('lib_localite')->join(', ');
    $numero = str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT);
@endphp

@section('titre-page', 'Attribution '.$numero)

@section('content')
<x-ui.page :titre="'Attribution '.$numero" class="max-w-5xl">
    <x-slot:actions>
        <x-ui.badge :ton="$attribution->statut->ton()" class="px-3 py-1.5">{{ $attribution->statut->libelle() }}</x-ui.badge>

        <x-ui.bouton :href="route('demandes.show', $demande->id_demande)" variante="secondaire">Consulter le dossier</x-ui.bouton>

        {{-- Tant que la séance est ouverte, la commission peut modifier ou annuler. La clôture vaut validation. --}}
        @if (auth()->user()->can(PermissionEnum::attribuerAppui->value) && $attribution->estModifiable())
            <x-ui.bouton :href="route('attributions.edit', $attribution->id_attribution)" variante="secondaire">Modifier</x-ui.bouton>
            <form action="{{ route('attributions.destroy', $attribution->id_attribution) }}" method="POST">
                @csrf
                @method('DELETE')
                <x-ui.bouton type="submit" variante="danger" onclick="return confirm('Voulez-vous confirmer l’annulation de cette attribution ?')">Annuler</x-ui.bouton>
            </form>
        @endif

        @if ($attribution->estImprimable())
            <x-ui.bouton :href="route('attributions.bon', $attribution->id_attribution)" target="_blank">
                <span class="material-symbols-outlined text-base">print</span> Imprimer le bon
            </x-ui.bouton>
            <x-ui.bouton :href="route('attributions.courrier', $attribution->id_attribution)" target="_blank" variante="secondaire">
                <span class="material-symbols-outlined text-base">mail</span> Imprimer le courrier
            </x-ui.bouton>
        @endif
    </x-slot:actions>

    <x-ui.card>
        <p class="text-sm text-slate-600">
            Demande {{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}, bénéficiaire :
            <span class="font-semibold text-[--color-primary]">{{ $nomEntite }}</span>
            @if ($demande?->nom_structure && $demande?->acteur?->nom)
                <span class="text-xs text-[--color-muted]">(Rep : {{ $demande->acteur->nom }})</span>
            @endif
            , {{ $demande?->typeActeur?->lib_type_acteur }}
            , localité(s) : {{ $localitesStr ?: 'Non spécifiée' }}
        </p>
    </x-ui.card>

    {{-- Les lignes attribuées --}}
    <x-ui.card titre="Détails des appuis attribués" sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3 text-center">Quantité</th>
                    <th class="px-4 py-3 text-right">Coût unitaire</th>
                    <th class="px-4 py-3 text-center">Remise</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($attribution->lignesAttributions as $ligne)
                    <tr>
                        <td class="px-4 py-3 font-medium text-[--color-ink]">
                            {{ $ligne->appui?->libelle }}
                            @if ($ligne->appui?->uniteDosage)
                                <span class="text-xs font-normal text-[--color-muted]">({{ $ligne->appui->uniteDosage->lib_unite }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-[--color-ink]">{{ $ligne->quantite_attribuee }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ number_format($ligne->cout_attribution, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-center">
                            @if ($ligne->remise > 0)
                                <x-ui.badge ton="vert">-{{ $ligne->remise }}%</x-ui.badge>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-[--color-ink]">{{ number_format($ligne->montant(), 0, ',', ' ') }} F</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-[--color-muted]">Aucune ligne enregistrée pour cette attribution.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-semibold">
                    <td colspan="4" class="px-4 py-3 text-right text-slate-700">Montant total global</td>
                    <td class="px-4 py-3 text-right text-lg font-bold text-[--color-primary]">{{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F</td>
                </tr>
            </tfoot>
        </x-ui.tableau>
    </x-ui.card>

    @if ($attribution->commentaires)
        <x-ui.card titre="Observations et motivations">
            <p class="rounded-lg border border-[--color-border] bg-slate-50 p-4 text-sm text-[--color-ink]">{{ $attribution->commentaires }}</p>
        </x-ui.card>
    @endif

    {{-- Sortie du magasin, une fois effectuée --}}
    @if ($attribution->date_destockage)
        <x-ui.card titre="Sortie de stock" class="border-emerald-200 bg-emerald-50/40">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-[--color-muted]">Date de sortie</dt>
                    <dd class="mt-0.5 text-[--color-ink]">{{ $attribution->date_destockage->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-[--color-muted]">Récipiendaire</dt>
                    <dd class="mt-0.5 text-[--color-ink]">{{ $attribution->recipiendaire }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-[--color-muted]">Pièce d'identité</dt>
                    <dd class="mt-0.5 text-[--color-ink]">{{ $attribution->numero_piece_identite ?? 'N/A' }}</dd>
                </div>
            </dl>
        </x-ui.card>
    @endif
</x-ui.page>
@endsection
