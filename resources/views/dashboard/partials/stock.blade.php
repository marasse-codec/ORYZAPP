{{-- Magasin : alertes de stock et sorties à effectuer --}}
<section>
    <h2 class="mb-3 text-base font-semibold text-slate-700">Stock</h2>
    <div class="grid gap-4 lg:grid-cols-3">
        <a href="{{ route('enlevements.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
            <p class="text-sm text-slate-500">Sorties à effectuer</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stock['sorties_a_effectuer'] }}</p>
        </a>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="mb-2 text-sm text-slate-500">Alertes de stock</p>
            @forelse ($stock['alertes'] as $ligne)
                <div class="flex items-center justify-between border-t border-slate-100 py-2 text-sm">
                    <span class="text-slate-700">{{ $ligne['appui']->libelle }}</span>
                    <span class="font-semibold {{ $ligne['stock'] === 0 ? 'text-rose-600' : 'text-amber-600' }}">
                        {{ $ligne['stock'] }} / seuil {{ $ligne['appui']->seuil_alerte }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun appui sous son seuil.</p>
            @endforelse
        </div>
    </div>
</section>
