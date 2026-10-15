@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Attributions')

@section('content')
<x-ui.page titre="Attributions" :sous-titre="$attributions->count().' bon(s) d\'attribution'">
    {{-- Le retour de rendement du DP sépare les bons : ceux qui l'attendent d'abord, puis ceux qui l'ont reçu. --}}
    @php
        $sections = [
            'attente' => ['Retour de rendement en attente', 'Publiées : le DP n\'a pas encore transmis le rendement de toutes les superficies.', 'ambre', 'hourglass_top'],
            'renseigne' => ['Retour de rendement renseigné', 'Toutes les superficies ont leur rendement.', 'vert', 'task_alt'],
            'autres' => ['Autres attributions', 'Pas encore publiées, ou sans superficie déclarée.', 'neutre', 'inventory_2'],
        ];
    @endphp

    @foreach ($sections as $cle => [$titreSection, $aide, $ton, $icone])
        @continue($groupes[$cle]->isEmpty() && ! ($cle === 'autres' && $attributions->isEmpty()))
        @php $lot = $groupes[$cle]; @endphp
        <x-ui.card sans-marge>
            <div class="flex items-center justify-between gap-3 border-b border-[--color-border] px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg text-[--color-muted]">{{ $icone }}</span>
                    <div>
                        <h2 class="text-sm font-semibold text-[--color-ink]">{{ $titreSection }}</h2>
                        <p class="text-xs text-[--color-muted]">{{ $aide }}</p>
                    </div>
                </div>
                <x-ui.badge :ton="$ton">{{ $lot->count() }}</x-ui.badge>
            </div>
            <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Bon</th>
                    <th class="px-4 py-3">Demandeur</th>
                    <th class="px-4 py-3">Séance</th>
                    <th class="px-4 py-3">Publication</th>
                    <th class="px-4 py-3 text-center">Lignes</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-center">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @foreach ($lot as $attribution)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-[--color-ink]">ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->nom }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $attribution->session?->date_debut_session?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $attribution->date_publication?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $attribution->lignesAttributions->count() }}</td>
                        <td class="px-4 py-3 text-right text-[--color-ink]">{{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-center">
                            <x-ui.badge :ton="$attribution->statut->ton()">{{ $attribution->statut->libelle() }}</x-ui.badge>
                            @if ($attribution->date_destockage)
                                <span class="block text-xs text-[--color-muted]">le {{ $attribution->date_destockage->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('attributions.show', $attribution->id_attribution) }}" class="text-sm font-medium text-[--color-primary] hover:underline">Lire</a>

                                @if (auth()->user()->peutModifierAttribution() && $attribution->estModifiable())
                                    <a href="{{ route('attributions.edit', $attribution->id_attribution) }}" class="text-sm font-medium text-[--color-button-secondary] hover:underline">Modifier</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if ($lot->isEmpty())
                    <tr><td colspan="8" class="px-4 py-8 text-center text-[--color-muted]">Aucune attribution enregistrée.</td></tr>
                @endif
            </tbody>
        </x-ui.tableau>
        </x-ui.card>
    @endforeach
</x-ui.page>
@endsection
