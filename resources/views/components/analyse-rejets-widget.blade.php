<?php

use Livewire\Component;
use App\Models\Demande;
use App\Enums\StatutDemandeE;

new class extends Component
{

public ?int $campagneId = null;

    public function render()
    {
        // Récupération des demandes rejetées
        $demandesRejetees = Demande::query()
            ->when($this->campagneId, fn ($q) => $q->where('id_campagne', $this->campagneId))
            // ->whereHas('statutDemande', function ($q) {
            //     $q->where(, 'LIKE', '%Rejet%')
            //       ->orWhere('libelleStatutDemande', 'LIKE', '%Attente%');
            // })
            ->join('lignes_demandes','demandes.id_demande','=','lignes_demandes.id_demande')
            ->where('id_statut_demande',StatutDemandeE::rejetee)
            ->select('motif_rejet_service', DB::raw('count(*) as total'))
            ->groupBy('motif_rejet_service')
            ->orderByDesc('total')
            ->get();
        
        $totalRejets = $demandesRejetees->sum('total');

        return view('components.analyse-rejets-widget', [
            'demandesRejetees' => $demandesRejetees,
            'totalRejets' => $totalRejets,
        ]);
    }};
?>

<div class="p-6 bg-white rounded-xl shadow-sm border border-slate-200">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Analyse des Motifs de Rejet</h3>
            <p class="text-xs text-slate-500">Répartition des demandes non satisfaites</p>
        </div>
        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-700">
            Total Rejets : {{ $totalRejets }}
        </span>
    </div>

    <!-- Graphique ou Liste des Motifs -->
    <div class="space-y-3 mt-4">
        @forelse($demandesRejetees as $item)
            @php 
                $pourcentage = $totalRejets > 0 ? round(($item->total / $totalRejets) * 100, 1) : 0; 
            @endphp
            <div>
                <div class="flex justify-between text-xs font-medium text-slate-700 mb-1">
                    <span class="truncate max-w-xs" title="{{ $item->motif_rejet_service }}">{{ $item->motif_rejet_service }}</span>
                    <span>{{ $item->total }} ({{ $pourcentage }}%)</span>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-rose-500 h-full rounded-full" style="width: {{ $pourcentage }}%"></div>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-sm text-slate-400">
                Aucun rejet enregistré pour cette campagne.
            </div>
        @endforelse
    </div>
</div>