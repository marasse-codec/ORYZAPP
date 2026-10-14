@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Catalogue des appuis')

@section('content')
<x-ui.page titre="Catalogue des appuis" sous-titre="Ce que le magasin peut distribuer. Le stock ne se modifie pas ici : il bouge par les approvisionnements et les retraits.">
    <x-slot:actions>
        <x-ui.bouton :href="route('appuis.create')">
            <span class="material-symbols-outlined text-base">add</span> Nouvel appui
        </x-ui.bouton>
    </x-slot:actions>

    {{-- Recherche et filtre par type : un simple formulaire GET, sans JavaScript. --}}
    <x-ui.card>
        <form method="GET" action="{{ route('appuis.index') }}" class="grid gap-3 sm:grid-cols-[1fr_16rem_auto] sm:items-end">
            <div>
                <x-input-label for="q" value="Rechercher" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$recherche" placeholder="Libellé de l'appui…" />
            </div>
            <div>
                <x-input-label for="type" value="Type d'appui" />
                <select id="type" name="type" class="mt-1 block w-full">
                    <option value="">Tous les types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id_type_appui }}" @selected((string) $idType === (string) $type->id_type_appui)>{{ $type->lib_type_appui }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.bouton type="submit" variante="secondaire">Filtrer</x-ui.bouton>
                @if ($recherche !== '' || $idType)
                    <x-ui.bouton :href="route('appuis.index')" variante="discret">Effacer</x-ui.bouton>
                @endif
            </div>
        </form>
    </x-ui.card>

    <x-ui.card sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Dose / ha</th>
                    <th class="px-4 py-3 text-right">Coût unitaire</th>
                    <th class="px-4 py-3 text-right">En stock</th>
                    <th class="px-4 py-3 text-right">Seuil d'alerte</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($appuis as $appui)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-[--color-ink]">{{ $appui->libelle }}</td>
                        <td class="px-4 py-3"><x-ui.badge>{{ $appui->typeAppui?->lib_type_appui }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ \App\Utils\Helper::quantite($appui->dose_ha) }} {{ $appui->uniteDosage?->lib_unite }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $appui->cout_unitaire !== null ? number_format($appui->cout_unitaire, 0, ',', ' ').' F' : '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            {{-- Sous le seuil : la quantité passe en rouge, comme dans les alertes. --}}
                            <span @class(['font-semibold', 'text-red-600' => $appui->seuil_alerte > 0 && $appui->quantite_en_stock <= $appui->seuil_alerte, 'text-[--color-ink]' => ! ($appui->seuil_alerte > 0 && $appui->quantite_en_stock <= $appui->seuil_alerte)])>
                                {{ \App\Utils\Helper::quantite($appui->quantite_en_stock) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $appui->seuil_alerte > 0 ? \App\Utils\Helper::quantite($appui->seuil_alerte) : '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('appuis.edit', $appui) }}" class="text-sm font-medium text-[--color-primary] hover:underline">Modifier</a>
                                <form method="POST" action="{{ route('appuis.destroy', $appui) }}"
                                      onsubmit="return confirm('Retirer « {{ addslashes($appui->libelle) }} » du catalogue ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-[--color-muted]">Aucun appui ne correspond.</td></tr>
                @endforelse
            </tbody>
        </x-ui.tableau>
    </x-ui.card>

    {{ $appuis->links() }}
</x-ui.page>
@endsection
