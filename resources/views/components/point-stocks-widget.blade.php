<?php

use Livewire\Component;
use App\Models\Appui;
use App\Models\Equipement;
new class extends Component
{
    public function render()
    {
        // 1. Récupération des appuis consommables et état de leurs stocks
        $appuis = Appui::query()
            ->with('typeAppui')
            ->withCount(['equipements as total_equipements'])
            ->withCount(['equipements as equipements_disponibles' => function ($query) {
                $query->where('en_service', false);
            }])
            ->get()
            ->map(function ($appui) {
                // Quantité réservée/engagée sur les bons d'attribution non encore enlevés
                $quantiteEngagee = DB::table('lignes_attributions')
                    ->where('id_appui', $appui->id_appui)
                    ->sum('quantite_attribuee');

                $stockDispo = $appui->quantite_en_stock - $quantiteEngagee;

                return [
                    'id' => $appui->id_appui,
                    'libelle' => $appui->libelle,
                    'est_intrant' => $appui->typeAppui->est_intrant,
                    'stock_physique' => $appui->quantite_en_stock,
                    'quantite_engagee' => $quantiteEngagee,
                    'stock_disponible' => $stockDispo,
                    'en_alerte' => $stockDispo <= 0,
                    'equipements_totaux' => $appui->total_equipements,
                    'equipements_parking' => $appui->equipements_disponibles,
                ];
            });
        
        // 2. Total récapitulatif du matériel roulant/équipements (Non consommables)
        $totalEquipements = Equipement::count();
        $equipementsEnService = Equipement::where('en_service', true)->count();
        $equipementsAuParking = $totalEquipements - $equipementsEnService;

        return view('components.point-stocks-widget', [
            'appuis' => $appuis,
            'totalEquipements' => $totalEquipements,
            'equipementsEnService' => $equipementsEnService,
            'equipementsAuParking' => $equipementsAuParking,
        ]);
    }
};
?>

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
                            @if(!$item['est_intrant'])
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