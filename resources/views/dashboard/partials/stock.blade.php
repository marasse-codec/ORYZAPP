{{-- Magasin : uniquement ce qui concerne les stocks (alertes, sorties à effectuer, équipements, derniers mouvements) --}}
<section class="space-y-3">
    <h2 class="text-base font-semibold text-[--color-ink]">Stock</h2>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-ui.stat libelle="Sorties à effectuer" :valeur="$stock['sorties_a_effectuer']" :href="route('enlevements.index')" :ton="$stock['sorties_a_effectuer'] > 0 ? 'ambre' : 'vert'" />
        <x-ui.stat libelle="Appuis sous le seuil" :valeur="$stock['alertes']->count()" :ton="$stock['alertes']->isNotEmpty() ? 'rouge' : 'vert'" />
        <x-ui.stat libelle="Appuis au catalogue" :valeur="$stock['nb_appuis']" :href="route('appuis.index')" />
        <x-ui.stat libelle="Équipements" :valeur="$stock['equipements_disponibles'].' disponibles'" :indice="$stock['equipements_en_service'].' en service'" :href="route('equipements.index')" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card titre="Alertes de stock" sans-marge>
            <ul class="divide-y divide-[--color-border]">
                @forelse ($stock['alertes'] as $ligne)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm sm:px-6">
                        <span class="min-w-0 truncate text-[--color-ink]">{{ $ligne['appui']->libelle }}</span>
                        <x-ui.badge :ton="$ligne['stock'] <= 0 ? 'rouge' : 'ambre'">{{ \App\Utils\Helper::quantite($ligne['stock']) }} / seuil {{ \App\Utils\Helper::quantite($ligne['appui']->seuil_alerte) }}</x-ui.badge>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-[--color-muted] sm:px-6">Aucun appui sous son seuil.</li>
                @endforelse
            </ul>
        </x-ui.card>

        <x-ui.card titre="Derniers mouvements" sans-marge>
            <x-slot:entete>
                <a href="{{ route('mouvements-stocks.index') }}" class="text-sm font-medium text-[--color-primary] hover:underline">Tout voir →</a>
            </x-slot:entete>
            <ul class="divide-y divide-[--color-border]">
                @forelse ($stock['derniers_mouvements'] as $mouvement)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm sm:px-6">
                        <span class="min-w-0 truncate text-[--color-ink]">
                            {{ $mouvement->appui?->libelle ?? 'Appui' }}
                            <span class="text-xs text-[--color-muted]">· {{ $mouvement->date_mouvement?->format('d/m/Y') }}</span>
                        </span>
                        <x-ui.badge>{{ $mouvement->typeMouvement?->libelle_type_mouvement }} · {{ \App\Utils\Helper::quantite($mouvement->quantite) }}</x-ui.badge>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-[--color-muted] sm:px-6">Aucun mouvement pour le moment.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</section>
