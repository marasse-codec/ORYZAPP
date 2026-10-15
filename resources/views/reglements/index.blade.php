@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Règlements')

@section('content')
<x-ui.page titre="Règlements" sous-titre="La caisse : ce qui est dû, ce qui est encaissé, et qui doit encore régler.">
    @include('dashboard.partials.reglements')

    <x-ui.card titre="Derniers règlements encaissés" sans-marge>
        @if ($paiements->isEmpty())
            <p class="p-4 text-sm text-[--color-muted] sm:p-6">Aucun règlement enregistré pour le moment.</p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Date</th>
                        <th class="px-4 py-3">Attribution</th>
                        <th class="px-4 py-3">Acteur</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 sm:px-6">Encaissé par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($paiements as $paiement)
                        <tr>
                            <td class="px-4 py-3 sm:px-6">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('attributions.show', $paiement->id_attribution) }}" class="font-medium text-[--color-primary] hover:underline">
                                    N° {{ str_pad($paiement->id_attribution, 3, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $paiement->attribution?->demande?->acteur?->nom ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $paiement->modeEnum()?->libelle() ?? $paiement->mode }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($paiement->montant, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 sm:px-6">{{ $paiement->encaissePar?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
