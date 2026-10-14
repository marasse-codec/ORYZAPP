@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Équipements')

@section('content')
<x-ui.page titre="Équipements" :sous-titre="$effectif['total'].' équipement(s) suivi(s) à l\'unité, dont '.$effectif['remis'].' remis à un bénéficiaire'">
    <x-slot:actions>
        <x-ui.bouton :href="route('equipements.retour.create')" variante="secondaire">
            <span class="material-symbols-outlined text-base">assignment_return</span> Enregistrer une restitution
        </x-ui.bouton>
    </x-slot:actions>

    <x-ui.card>
        <form method="GET" action="{{ route('equipements.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_12rem_12rem_auto] lg:items-end">
            <div>
                <x-input-label for="q" value="Rechercher" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$recherche" placeholder="Matricule ou appui…" />
            </div>
            <div>
                <x-input-label for="etat" value="État" />
                <select id="etat" name="etat" class="mt-1 block w-full">
                    <option value="">Tous</option>
                    @foreach ($etats as $valeur)
                        <option value="{{ $valeur }}" @selected($etat === $valeur)>{{ $valeur }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="situation" value="Situation" />
                <select id="situation" name="situation" class="mt-1 block w-full">
                    <option value="">Toutes</option>
                    <option value="parking" @selected($situation === 'parking')>En parking</option>
                    <option value="remis" @selected($situation === 'remis')>Remis</option>
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.bouton type="submit" variante="secondaire">Filtrer</x-ui.bouton>
                @if ($recherche !== '' || $etat || $situation)
                    <x-ui.bouton :href="route('equipements.index')" variante="discret">Effacer</x-ui.bouton>
                @endif
            </div>
        </form>
    </x-ui.card>

    <x-ui.card sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Matricule</th>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3">État</th>
                    <th class="px-4 py-3 text-center">Situation</th>
                    <th class="px-4 py-3">Entré le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($equipements as $equipement)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-[--color-ink]">{{ $equipement->matricule_equipement }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $equipement->appui?->libelle ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $equipement->etat ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($equipement->en_service)
                                <x-ui.badge ton="vert">Remis</x-ui.badge>
                            @else
                                <x-ui.badge>En parking</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $equipement->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-[--color-muted]">Aucun équipement ne correspond.</td></tr>
                @endforelse
            </tbody>
        </x-ui.tableau>
    </x-ui.card>

    {{ $equipements->links() }}
</x-ui.page>
@endsection
