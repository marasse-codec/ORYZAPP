<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Demande;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int $campagneId = null;

    public function render()
    {
        // Répartition des demandes par type d'entité

    $repartition = Demande::query()
        ->when($this->campagneId, fn ($q) => $q->where('demandes.id_campagne', $this->campagneId))
        ->join('types_acteurs', 'types_acteurs.id_type_acteur', '=', 'demandes.id_type_acteur')
        ->leftJoin('systemes_production', 'systemes_production.id_demande', '=', 'demandes.id_demande')
        ->select(
            'types_acteurs.lib_type_acteur as type_entite', // ou types_acteurs.nom
            DB::raw('COUNT(DISTINCT demandes.id_demande) as total_demandes'),
            DB::raw('COALESCE(SUM(systemes_production.superficie), 0) as total_superficie')
        )
        ->groupBy('types_acteurs.id_type_acteur', 'types_acteurs.lib_type_acteur')
        ->orderByDesc('total_demandes')
        ->get();

        $totalGeneral = $repartition->sum('total_demandes');

        return view('components.repartition-beneficiaires-widget', [
            'repartition'  => $repartition,
            'totalGeneral' => $totalGeneral,
        ]);
    }
}
?>

<div class="p-5 bg-white rounded-xl shadow-sm border border-slatelibelle-200">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Répartition par Type d'Acteur</h3>
            <p class="text-xs text-slate-400">Distribution des dossiers par statut d'entité</p>
        </div>
        <span class="text-xs font-bold bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full">
            {{ number_format($totalGeneral) }} Dossiers
        </span>
    </div>

    <div class="space-y-3">
        @forelse($repartition as $item)
            @php
                $pourcentage = $totalGeneral > 0 ? round(($item->total_demandes / $totalGeneral) * 100, 1) : 0;
            @endphp
            <div>
                <div class="flex justify-between items-center text-xs mb-1">
                    <span class="font-medium text-slate-700 capitalize">{{ $item->type_entite ?? 'Non spécifié' }}</span>
                    <div class="space-x-2 text-right">
                        <span class="font-bold text-slate-800">{{ $item->total_demandes }}</span>
                        <span class="text-slate-400">({{ $pourcentage }}%)</span>
                    </div>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-teal-500 h-full rounded-full transition-all duration-300" style="width: {{ min($pourcentage, 100) }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-xs text-slate-400 italic">Aucune donnée disponible pour cette campagne.</p>
        @endforelse
    </div>
</div>