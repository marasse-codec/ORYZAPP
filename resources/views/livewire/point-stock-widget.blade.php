<div class="p-6 bg-white rounded-xl shadow-sm border border-slate-200">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Point des Stocks & Matériels</h3>
            <p class="text-xs text-slate-500">Disponibilités physiques après attribution</p>
        </div>
        <a href="{{ route('appuis.index') }}" class="text-xs font-semibold text-[--color-primary] hover:underline">
            Gérer le stock →
        </a>
    </div>

    <!-- Synthèse Équipements Roulants / Matériel -->
    @if($totalEquipements > 0)
        <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg mb-4 text-center">
            <div>
                <span class="block text-xs text-slate-500">Équipements sur le terrain</span>
                <span class="text-lg font-bold text-amber-600">{{ $equipementsEnService }} / {{ $totalEquipements }}</span>
            </div>
            <div>
                <span class="block text-xs text-slate-500">Disponibles au parking</span>
                <span class="text-lg font-bold text-emerald-600">{{ $equipementsAuParking }}</span>
            </div>
        </div>
    @endif

    <!-- Tableau synthétique des Stocks -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold uppercase">
                <tr>
                    <th class="p-2.5">Désignation</th>
                    <th class="p-2.5 text-center">Stock Phys.</th>
                    <th class="p-2.5 text-center">Attribué</th>
                    <th class="p-2.5 text-right">Reste Dispo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($appuis as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="p-2.5 font-medium text-slate-800">
                            {{ $item['libelle'] }}
                            @if(!$item['est_consommable'])
                                <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-slate-200 text-slate-700 rounded">Matériel</span>
                            @endif
                        </td>
                        <td class="p-2.5 text-center font-semibold">{{ number_format($item['stock_physique']) }}</td>
                        <td class="p-2.5 text-center text-amber-600 font-semibold">{{ number_format($item['quantite_engagee']) }}</td>
                        <td class="p-2.5 text-right font-bold {{ $item['en_alerte'] ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ number_format($item['stock_disponibilite'] ?? $item['stock_disponible']) }}
                            @if($item['en_alerte'])
                                <span class="inline-block w-2 h-2 bg-rose-500 rounded-full ml-1" title="Rupture ou sur-attribution"></span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-slate-400">Aucun appui enregistré dans le catalogue.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>