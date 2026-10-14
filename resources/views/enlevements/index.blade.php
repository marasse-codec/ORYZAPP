@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Sorties de stock')

@section('content')
<x-ui.page titre="Sorties de stock" sous-titre="Bons dont les résultats sont publiés, en attente de remise au demandeur.">

    {{-- Le récipiendaire se présente avec son bon ou son seul téléphone. --}}
    <x-ui.card>
        <form method="GET" action="{{ route('enlevements.index') }}">
            <x-input-label for="recherche" value="Rechercher un bon" />
            <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                <x-text-input id="recherche" name="recherche" type="search" class="block flex-1" :value="$recherche"
                              placeholder="N° de bon (ex. 12), téléphone ou nom du demandeur" />
                <x-ui.bouton type="submit">Rechercher</x-ui.bouton>
                @if ($recherche !== '')
                    <x-ui.bouton :href="route('enlevements.index')" variante="secondaire">Réinitialiser</x-ui.bouton>
                @endif
            </div>
            @if ($recherche !== '')
                <p class="mt-2 text-xs text-[--color-muted]">{{ $enlevements->count() }} résultat(s) pour « {{ $recherche }} ».</p>
            @endif
        </form>
    </x-ui.card>

    <x-ui.card sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Bon</th>
                    <th class="px-4 py-3">Bénéficiaire</th>
                    <th class="px-4 py-3">Téléphone</th>
                    <th class="px-4 py-3 text-center">Lignes</th>
                    <th class="px-4 py-3 text-center">État</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($enlevements as $attribution)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-[--color-ink]">
                            ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}
                            <span class="block text-xs font-normal text-[--color-muted]">Séance clôturée le {{ $attribution->session?->date_fin_session?->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->nom }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->telephone }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $attribution->lignesAttributions->count() }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($attribution->date_destockage)
                                <x-ui.badge ton="vert">Servie le {{ $attribution->date_destockage->format('d/m/Y') }}</x-ui.badge>
                                @if ($attribution->recipiendaire)
                                    <span class="mt-1 block text-xs text-[--color-muted]">à {{ $attribution->recipiendaire }}</span>
                                @endif
                            @else
                                <x-ui.badge ton="ambre">À servir</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($attribution->date_destockage)
                                <a href="{{ route('attributions.bon', $attribution->id_attribution) }}" class="text-sm font-medium text-[--color-primary] hover:underline">Bon</a>
                            @else
                                <x-ui.bouton :href="route('enlevements.create', $attribution->id_attribution)" class="px-3 py-1.5 text-xs">Servir</x-ui.bouton>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-[--color-muted]">{{ $recherche !== '' ? 'Aucun bon ne correspond à cette recherche.' : 'Aucun bon à servir.' }}</td></tr>
                @endforelse
            </tbody>
        </x-ui.tableau>
    </x-ui.card>
</x-ui.page>
@endsection
