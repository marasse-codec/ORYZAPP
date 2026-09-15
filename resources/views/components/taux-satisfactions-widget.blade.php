<?php

use Livewire\Component;
use App\Models\LigneDemande;
use App\Models\Demande;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int $campagneId = null;
    public ?int $referenceAppuiId = null;

    public function with(): array
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

        return [
            'totalDemande'          => $totalDemande,
            'totalAttribue'         => $totalAttribue,
            'tauxQuantitatif'       => $tauxQuantitatif,
            'totalDemandesCount'    => $totalDemandesCount,
            'demandesValideesCount' => $demandesValideesCount,
            'tauxDossiers'          => $tauxDossiers,
        ];
    }
};
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-4">
    <!-- Card Taux Volumétrique -->
    <div class="p-5 bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
            Taux de Satisfaction Physique
        </div>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-3xl font-extrabold text-emerald-600">{{ $tauxQuantitatif }}%</span>
            <span class="text-xs text-slate-500">{{ number_format($totalAttribue) }} / {{ number_format($totalDemande) }} unités</span>
        </div>
        <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
            <div class="bg-emerald-500 h-full rounded-full" style="width: {{ min($tauxQuantitatif, 100) }}%"></div>
        </div>
    </div>

    <!-- Card Taux Approbation Demandes -->
    <div class="p-5 bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
            Taux d'Approbation des Dossiers
        </div>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-3xl font-extrabold text-blue-600">{{ $tauxDossiers }}%</span>
            <span class="text-xs text-slate-500">{{ $demandesValideesCount }} / {{ $totalDemandesCount }} demandes</span>
        </div>
        <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
            <div class="bg-blue-500 h-full rounded-full" style="width: {{ min($tauxDossiers, 100) }}%"></div>
        </div>
    </div>
</div>