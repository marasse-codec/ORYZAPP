@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Règlements')

@section('content')
<x-ui.page titre="Règlements" sous-titre="Les brouillons à vérifier puis soumettre, et l'historique des règlements soumis.">
    <div class="flex gap-2">
        <x-ui.bouton :href="route('comptabilite.reglements', ['etat' => 'brouillon'])" :variante="$etat === 'brouillon' ? 'primaire' : 'secondaire'">En brouillon</x-ui.bouton>
        <x-ui.bouton :href="route('comptabilite.reglements', ['etat' => 'soumis'])" :variante="$etat === 'soumis' ? 'primaire' : 'secondaire'">Soumis</x-ui.bouton>
    </div>

    <x-ui.card :titre="$paiements->count().' règlement(s) '.($etat === 'soumis' ? 'soumis (100 derniers)' : 'en brouillon')" sans-marge>
        @if ($paiements->isEmpty())
            <p class="p-4 text-sm text-[--color-muted] sm:p-6">{{ $etat === 'soumis' ? 'Aucun règlement soumis.' : 'Aucun brouillon en attente.' }}</p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Date</th>
                        <th class="px-4 py-3">Attribution</th>
                        <th class="px-4 py-3">Demandeur</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3">Saisi par</th>
                        <th class="px-4 py-3 sm:px-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($paiements as $paiement)
                        <tr>
                            <td class="px-4 py-3 sm:px-6">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">ATT-{{ str_pad($paiement->id_attribution, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-4 py-3">{{ $paiement->attribution?->demande?->acteur?->nom ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $paiement->modeEnum()?->libelle() ?? $paiement->mode }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($paiement->montant, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3">{{ $paiement->encaissePar?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right sm:px-6">
                                <x-ui.bouton :href="route('comptabilite.reglement', $paiement->id_attribution)" variante="secondaire">
                                    {{ $etat === 'soumis' ? 'Voir' : 'Vérifier et soumettre' }}
                                </x-ui.bouton>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
