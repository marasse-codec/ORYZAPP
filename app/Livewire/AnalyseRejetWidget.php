<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Demande;
use Illuminate\Support\Facades\DB;

class AnalyseRejetsWidget extends Component
{
    public ?int $campagneId = null;

    public function render()
    {
        // Récupération des demandes rejetées
        $demandesRejetees = Demande::query()
            ->when($this->campagneId, fn ($q) => $q->where('id_campagne', $this->campagneId))
            ->whereHas('statutDemande', function ($q) {
                $q->where('libelleStatutDemande', 'LIKE', '%Rejet%')
                  ->orWhere('libelleStatutDemande', 'LIKE', '%Attente%');
            })
            ->whereNotNull('motifRejet')
            ->select('motifRejet', DB::raw('count(*) as total'))
            ->groupBy('motifRejet')
            ->orderByDesc('total')
            ->get();

        $totalRejets = $demandesRejetees->sum('total');

        return view('components.analyse-rejets-widget', [
            'demandesRejetees' => $demandesRejetees,
            'totalRejets' => $totalRejets,
        ]);
    }
}