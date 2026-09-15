<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\LigneDemande;
use App\Models\Demande;
use Illuminate\Support\Facades\DB;

class TauxSatisfactionsWidget extends Component
{
    public ?int $campagneId = null;
    public ?int $referenceAppuiId = null;

    public function render()
    {
        $query = LigneDemande::query()
            ->when($this->campagneId, fn ($q) => $q->whereHas('demande', fn ($d) => $d->where('id_campagne', $this->campagneId)))
            ->when($this->referenceAppuiId, fn ($q) => $q->where('id_reference_appui', $this->referenceAppuiId));

        $totalDemande = (int) $query->sum('quantite_demandee');

        $totalAttribue = (int) DB::table('lignes_attributions')
            ->join('attributions', 'lignes_attributions.id_attribution', '=', 'attributions.id_attribution')
            ->join('demandes', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->when($this->campagneId, fn ($q) => $q->where('demandes.id_campagne', $this->campagneId))
            ->when($this->referenceAppuiId, fn ($q) => $q->where('lignes_demandes.id_reference_appui', $this->referenceAppuiId))
            ->sum('lignes_attributions.quantite_attribuee');

        $tauxQuantitatif = $totalDemande > 0 
            ? round(($totalAttribue / $totalDemande) * 100, 2) 
            : 0;

        $totalDemandesCount = Demande::query()
            ->when($this->campagneId, fn ($q) => $q->where('id_campagne', $this->campagneId))
            ->count();

        $demandesValideesCount = Demande::query()
            ->when($this->campagneId, fn ($q) => $q->where('id_campagne', $this->campagneId))
            ->whereHas('attribution')
            ->count();

        $tauxDossiers = $totalDemandesCount > 0 
            ? round(($demandesValideesCount / $totalDemandesCount) * 100, 2) 
            : 0;

        return view('components.taux-satisfactions-widget', [
            'totalDemande'          => $totalDemande,
            'totalAttribue'         => $totalAttribue,
            'tauxQuantitatif'       => $tauxQuantitatif,
            'totalDemandesCount'    => $totalDemandesCount,
            'demandesValideesCount' => $demandesValideesCount,
            'tauxDossiers'          => $tauxDossiers,
        ]);
    }
}