@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Approvisionnements')

@section('content')
<x-ui.page titre="Approvisionnements" :sous-titre="$approvisionnements->count().' entrée(s) de stock'">
    <x-slot:actions>
        <x-ui.bouton :href="route('approvisionnements.create')">
            <span class="material-symbols-outlined text-base">add</span> Enregistrer une entrée
        </x-ui.bouton>
    </x-slot:actions>

    <x-ui.card sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3">Référence commande</th>
                    <th class="px-4 py-3">Origine</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($approvisionnements as $appro)
                    @foreach ($appro->mouvements as $mouvement)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-600">{{ $appro->date_approvisionnement?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-medium text-[--color-ink]">{{ $mouvement->appui?->libelle }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-700">+{{ $mouvement->total_quantite }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $appro->reference_commande ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $appro->origine ?? '-' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-[--color-muted]">Aucun approvisionnement enregistré.</td></tr>
                @endforelse
            </tbody>
        </x-ui.tableau>
    </x-ui.card>
</x-ui.page>
@endsection
