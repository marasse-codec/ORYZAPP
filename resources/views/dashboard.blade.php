@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Tableau de bord')

@section('content')
<x-ui.page titre="Tableau de bord" :sous-titre="array_sum($statuts).' demande(s) dans votre périmètre'">
    @if ($peutCreer)
        <x-slot:actions>
            <x-ui.bouton :href="route('demandes.create')">Nouvelle demande</x-ui.bouton>
        </x-slot:actions>
    @endif

    {{-- Demandes par statut : tous les rôles --}}
    <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @foreach ($cas as $statut)
            @php
                // DP : la dernière demande de ce statut ; sans demande, la carte n'est pas cliquable.
                $lien = $dernieres
                    ? (($id = $dernieres[$statut->value] ?? null) ? route('demandes.show', $id) : null)
                    : route('demandes.index');
            @endphp
            <x-ui.stat :libelle="$statut->libelle()" :valeur="$statuts[$statut->value]"
                       :ton="$statut->ton()" :href="$lien" />
        @endforeach
    </section>

    {{-- Superficies : déclarées par les demandes soumises, et traitables selon les attributions --}}
    <section class="grid grid-cols-2 gap-3 sm:gap-4">
        <x-ui.stat libelle="Superficie concernée" :valeur="number_format($superficies['concernee'], 2, ',', ' ').' ha'" />
        <x-ui.stat libelle="Superficie traitée" ton="vert"
                   :valeur="number_format($superficies['traitee'], 2, ',', ' ').' ha'"
                   :indice="$superficies['taux'] !== null ? number_format($superficies['taux'], 1, ',', ' ').' % de la superficie concernée' : null" />
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <x-ui.card titre="Répartition des demandes">
            <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueStatuts) }}"></canvas></div>
        </x-ui.card>

        <x-ui.card titre="Demandes soumises par campagne">
            @if ($graphiqueCampagnes)
                <div class="h-64"><canvas data-chart="{{ json_encode($graphiqueCampagnes) }}"></canvas></div>
            @else
                <p class="text-sm text-[--color-muted]">Aucune demande soumise.</p>
            @endif
        </x-ui.card>
    </section>

    @includeWhen($commission, 'dashboard.partials.commission', ['commission' => $commission])
    @includeWhen($stock, 'dashboard.partials.stock', ['stock' => $stock])
    @includeWhen($reglements, 'dashboard.partials.reglements', ['reglements' => $reglements])

    {{-- Dernières attributions visibles par le rôle (DP : publiées uniquement) --}}
    <x-ui.card titre="Dernières attributions" sans-marge>
        <x-slot:entete>
            <a href="{{ route('attributions.index') }}" class="text-sm font-medium text-[--color-primary] hover:underline">Tout voir →</a>
        </x-slot:entete>

        <ul class="divide-y divide-[--color-border]">
            @forelse ($attributions as $attribution)
                <li>
                    <a href="{{ route('attributions.show', $attribution->id_attribution) }}"
                       class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-3 text-sm hover:bg-slate-50 sm:px-6">
                        <span class="font-medium text-[--color-ink]">{{ $attribution->demande?->acteur?->nom ?? 'Attribution' }}</span>
                        <x-ui.badge :ton="$attribution->statut->ton()">{{ $attribution->statut->libelle() }}</x-ui.badge>
                    </a>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-[--color-muted] sm:px-6">Aucune attribution pour le moment.</li>
            @endforelse
        </ul>
    </x-ui.card>

</x-ui.page>
@endsection

@push('scripts')
    @vite('resources/js/charts.js')
@endpush
