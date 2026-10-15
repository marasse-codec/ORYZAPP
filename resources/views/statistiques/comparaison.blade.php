@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Comparer les campagnes')

@section('content')
<x-ui.page titre="Comparer les campagnes" sous-titre="Les mêmes indicateurs, calculés de la même façon pour chaque campagne, de la plus ancienne à la plus récente.">
    @include('statistiques.partials.filtres', ['action' => route('statistiques.comparaison'), 'campagnesACocher' => $choisies])

    @if ($lignes->isEmpty())
        <x-ui.card><p class="text-sm text-[--color-muted]">Aucune campagne à comparer.</p></x-ui.card>
    @else
        <x-ui.card titre="Indicateurs par campagne" sans-marge>
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Indicateur</th>
                        @foreach ($lignes as $ligne)
                            <th class="px-4 py-3 text-right">{{ $ligne['campagne']->lib_campagne }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($indicateurs as $cle => [$libelle, $unite])
                        <tr>
                            <td class="px-4 py-3 font-medium text-[--color-ink] sm:px-6">{{ $libelle }}</td>
                            @foreach ($lignes as $ligne)
                                @php
                                    $valeur = $ligne['valeurs'][$cle];
                                    $evolution = $ligne['evolutions'][$cle];
                                @endphp
                                <td class="px-4 py-3 text-right">
                                    {{ $valeur !== null ? number_format($valeur, in_array($unite, ['%', 'j', 't/ha', 'ha']) ? 1 : 0, ',', ' ').($unite ? ' '.$unite : '') : '-' }}
                                    @if ($evolution !== null)
                                        {{-- Évolution par rapport à la campagne précédente (colonne de gauche). --}}
                                        <span class="block text-xs {{ $evolution >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ $evolution >= 0 ? '+' : '' }}{{ number_format($evolution, 1, ',', ' ') }} %
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
            <p class="px-4 py-3 text-xs text-[--color-muted] sm:px-6">
                Sous chaque valeur : l'évolution par rapport à la campagne précédente du tableau. Pour un délai, une baisse est une amélioration.
            </p>
        </x-ui.card>

        <section class="grid gap-4 lg:grid-cols-2">
            <x-ui.card titre="Volumes">
                @if ($graphiqueVolumes)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueVolumes) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Choisissez au moins deux campagnes.</p>
                @endif
            </x-ui.card>
            <x-ui.card titre="Taux (%)">
                @if ($graphiqueTaux)
                    <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueTaux) }}"></canvas></div>
                @else
                    <p class="text-sm text-[--color-muted]">Choisissez au moins deux campagnes.</p>
                @endif
            </x-ui.card>
        </section>
    @endif
</x-ui.page>
@endsection

@push('scripts')
    @vite('resources/js/charts.js')
@endpush
