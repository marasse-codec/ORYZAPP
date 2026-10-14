{{-- Magasin : alertes de stock et sorties à effectuer --}}
<section class="space-y-3">
    <h2 class="text-base font-semibold text-[--color-ink]">Stock</h2>
    <div class="grid gap-3 sm:gap-4 lg:grid-cols-3">
        <x-ui.stat libelle="Sorties à effectuer" :valeur="$stock['sorties_a_effectuer']" :href="route('enlevements.index')" ton="ambre" />

        <x-ui.card titre="Alertes de stock" sans-marge class="lg:col-span-2">
            <ul class="divide-y divide-[--color-border]">
                @forelse ($stock['alertes'] as $ligne)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm sm:px-6">
                        <span class="min-w-0 truncate text-[--color-ink]">{{ $ligne['appui']->libelle }}</span>
                        <x-ui.badge :ton="$ligne['stock'] === 0 ? 'rouge' : 'ambre'">{{ $ligne['stock'] }} / seuil {{ $ligne['appui']->seuil_alerte }}</x-ui.badge>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-[--color-muted] sm:px-6">Aucun appui sous son seuil.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</section>
