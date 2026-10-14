@extends($layout ?? 'layouts.aderiz')

@section('titre-page', "Fiches d'arbitrage")

@section('content')
<div x-data="{ nb: 0, maj() { this.nb = $el.querySelectorAll('.case-demande:checked').length; } }">
    <div class="max-w-6xl mx-auto">
        <form action="{{ route('editions.arbitrages.imprimer') }}" method="POST" target="_blank">
            @csrf

            <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-xl font-bold text-[--color-primary] sm:text-2xl">Fiches d'arbitrage</h1>
                    <p class="text-sm text-slate-500">
                        {{ $demandes->count() }} demande(s) en attente d'arbitrage. La fiche est un brouillon papier :
                        la commission la remplit en séance, l'agent saisit ensuite l'attribution.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="nb === 0"
                            :class="nb === 0 ? 'cursor-not-allowed bg-slate-400 opacity-50' : 'bg-emerald-700 hover:bg-emerald-800'"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white transition">
                        <span class="material-symbols-outlined text-base">print</span>
                        <span x-text="nb > 0 ? 'Imprimer la sélection (' + nb + ')' : 'Imprimer la sélection'"></span>
                    </button>

                    @if ($demandes->isNotEmpty())
                        <a href="{{ route('editions.arbitrages.imprimer') }}" target="_blank"
                           class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">
                            Imprimer tout
                        </a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="tableau-carte min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-center">
                                <input type="checkbox" aria-label="Tout cocher"
                                       @click="$el.closest('table').querySelectorAll('.case-demande').forEach(c => c.checked = $el.checked); maj()"
                                       class="rounded border-gray-300 text-[--color-primary] focus:ring-[--color-primary]">
                            </th>
                            <th class="px-4 py-3">Demande</th>
                            <th class="px-4 py-3">Demandeur</th>
                            <th class="px-4 py-3">Localité(s)</th>
                            <th class="px-4 py-3">Soumise le</th>
                            <th class="px-4 py-3 text-right">Superficie (ha)</th>
                            <th class="px-4 py-3">Appuis sollicités</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($demandes as $demande)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="demandes[]" value="{{ $demande->id_demande }}" @change="maj()"
                                           class="case-demande rounded border-gray-300 text-[--color-primary] focus:ring-[--color-primary]">
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800">DEM-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $demande->nom_structure ?? $demande->acteur?->nom }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $demande->localites->pluck('lib_localite')->join(', ') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $demande->date_soummission?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right text-slate-800">{{ number_format($demande->systemesProductions->sum('superficie'), 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $demande->lignes->map(fn ($l) => $l->typeAppui?->lib_type_appui)->filter()->join(', ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Aucune demande n'attend l'arbitrage.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
@endsection
