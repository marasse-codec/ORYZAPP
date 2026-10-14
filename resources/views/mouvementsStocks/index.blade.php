@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Mouvements de stock')

@section('content')
<x-ui.page titre="Mouvements de stock" sous-titre="Le registre des entrées, sorties et retours du magasin.">

    <x-ui.card>
        <form method="GET" action="{{ route('mouvements-stocks.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_12rem_10rem_10rem_auto] lg:items-end">
            <div>
                <x-input-label for="search" value="Rechercher" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="request('search')" placeholder="Appui ou agent…" />
            </div>
            <div>
                <x-input-label for="id_type_mouvement" value="Type" />
                <select id="id_type_mouvement" name="id_type_mouvement" class="mt-1 block w-full">
                    <option value="">Tous les types</option>
                    @foreach ($typesMouvements as $type)
                        <option value="{{ $type->id_type_mouvement_stock }}" @selected((string) request('id_type_mouvement') === (string) $type->id_type_mouvement_stock)>{{ $type->libelle_type_mouvement }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="date_debut" value="Du" />
                <x-text-input id="date_debut" name="date_debut" type="date" class="mt-1 block w-full" :value="request('date_debut')" />
            </div>
            <div>
                <x-input-label for="date_fin" value="Au" />
                <x-text-input id="date_fin" name="date_fin" type="date" class="mt-1 block w-full" :value="request('date_fin')" />
            </div>
            <div class="flex gap-2">
                <x-ui.bouton type="submit" variante="secondaire">Filtrer</x-ui.bouton>
                @if (request()->hasAny(['search', 'id_type_mouvement', 'date_debut', 'date_fin']))
                    <x-ui.bouton :href="route('mouvements-stocks.index')" variante="discret">Effacer</x-ui.bouton>
                @endif
            </div>
        </form>
    </x-ui.card>

    <x-ui.card sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3">Agent</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($mouvements as $mouvement)
                    @php
                        $ton = match ($mouvement->typeMouvement?->code) {
                            'ENTREE', 'RETOUR' => 'vert',
                            'SORTIE' => 'ambre',
                            default => 'neutre',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-600">{{ $mouvement->date_mouvement?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3"><x-ui.badge :ton="$ton">{{ $mouvement->typeMouvement?->libelle_type_mouvement ?? 'Mouvement' }}</x-ui.badge></td>
                        <td class="px-4 py-3 font-medium text-[--color-ink]">
                            {{ $mouvement->appui?->libelle ?? '-' }}
                            @if ($mouvement->equipement)
                                <span class="block text-xs font-normal text-[--color-muted]">{{ $mouvement->equipement->matricule_equipement }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-[--color-ink]">{{ $mouvement->quantite }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $mouvement->agent?->nom }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('mouvements-stocks.show', $mouvement->id_mouvement_stock) }}" class="text-sm font-medium text-[--color-primary] hover:underline">Détails</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-[--color-muted]">Aucun mouvement trouvé.</td></tr>
                @endforelse
            </tbody>
        </x-ui.tableau>
    </x-ui.card>

    {{ $mouvements->links() }}
</x-ui.page>
@endsection
