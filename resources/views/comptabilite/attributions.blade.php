@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Attributions à encaisser')

@section('content')
<x-ui.page titre="Attributions à encaisser" sous-titre="Retrouvez une attribution publiée, puis ouvrez sa page de règlement.">
    <x-ui.card>
        <form method="GET" action="{{ route('comptabilite.attributions') }}" class="flex flex-wrap items-end gap-3">
            <label class="min-w-0 flex-1 text-sm">
                <span class="text-[--color-muted]">Rechercher</span>
                <input type="search" name="q" value="{{ $terme }}" autofocus
                       placeholder="N° d'attribution (ATT-12), n° de demande (DEM-7), téléphone ou nom du demandeur"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </label>
            <label class="inline-flex items-center gap-2 pb-2 text-sm text-slate-700">
                <input type="checkbox" name="toutes" value="1" @checked($toutes) class="rounded border-slate-300">
                Inclure les attributions soldées
            </label>
            <x-ui.bouton type="submit">Rechercher</x-ui.bouton>
            @if ($terme || $toutes)
                <x-ui.bouton :href="route('comptabilite.attributions')" variante="secondaire">Effacer</x-ui.bouton>
            @endif
        </form>
    </x-ui.card>

    <x-ui.card :titre="$attributions->count().' attribution(s)'" sans-marge>
        @if ($attributions->isEmpty())
            <p class="p-4 text-sm text-[--color-muted] sm:p-6">
                @if ($terme)
                    Aucune attribution ne correspond à « {{ $terme }} ».
                @else
                    {{ $toutes ? 'Aucune attribution publiée pour le moment.' : 'Toutes les attributions publiées sont soldées.' }}
                @endif
            </p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Attribution</th>
                        <th class="px-4 py-3">Demandeur</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-right">Réglé</th>
                        <th class="px-4 py-3 text-right">Reste</th>
                        <th class="px-4 py-3 sm:px-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($attributions as $attribution)
                        @php $reste = $attribution->resteDu(); @endphp
                        <tr>
                            <td class="px-4 py-3 sm:px-6">
                                <span class="font-semibold text-[--color-ink]">ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="block text-xs text-[--color-muted]">DEM-{{ $attribution->id_demande }} · publiée le {{ $attribution->date_publication->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $attribution->demande?->nom_structure ?: ($attribution->demande?->acteur?->nom ?? '-') }}
                                <span class="block text-xs text-[--color-muted]">{{ $attribution->demande?->acteur?->telephone }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-right">{{ number_format($attribution->totalPaye(), 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-right">
                                @if ($reste > 0)
                                    <x-ui.badge ton="rouge">{{ number_format($reste, 0, ',', ' ') }} F</x-ui.badge>
                                @else
                                    <x-ui.badge ton="vert">Soldée</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right sm:px-6">
                                <x-ui.bouton :href="route('comptabilite.reglement', $attribution->id_attribution)" :variante="$reste > 0 ? 'primaire' : 'secondaire'">
                                    {{ $reste > 0 ? 'Enregistrer un règlement' : 'Voir' }}
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
