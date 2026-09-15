<?php

namespace App\Livewire;

use App\Models\Demande;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public ?int $campagneId = null;

    public function render()
    {
        $regions = Demande::query()
            ->when($this->campagneId, fn ($q) => $q->where('demandes.id_campagne', $this->campagneId))
            ->join('localites', 'localites.id_localite', '=', 'demandes.id_localite')
            ->join('departements', 'departements.id_departement', '=', 'localites.id_departement')
            ->leftJoin('systemes_production', 'systemes_production.id_demande', '=', 'demandes.id_demande')
            ->select(
                'departements.lib_departement as region',
                DB::raw('COUNT(DISTINCT demandes.id_demande) as nombre_demandes'),
                DB::raw('COALESCE(SUM(systemes_production.superficie), 0) as superficie_validee')
            )
            ->groupBy('departements.id_departement', 'departements.lib_departement')
            ->orderByDesc('nombre_demandes')
            ->get();

        $maxDemandes = $regions->max('nombre_demandes') ?: 1;

        return view('components.repartition-regionale-widget', [
            'regions'     => $regions,
            'maxDemandes' => $maxDemandes,
        ]);
    }
}

?>

<div class="p-5 bg-white rounded-xl shadow-sm border border-slate-200">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Couverture Régionale</h3>
            <p class="text-xs text-slate-400">Volume des demandes et superficies validées par Pôle / Région</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @forelse($regions as $item)
            @php
                $ratio = round(($item->nombre_demandes / $maxDemandes) * 100);
            @endphp
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-xs font-bold uppercase text-slate-700 tracking-wide">{{ $item->region ?? 'Autre' }}</span>
                    <span class="text-xs font-semibold px-2 py-0.5 bg-blue-100 text-blue-800 rounded">
                        {{ $item->nombre_demandes }} demandes
                    </span>
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    Sup. Validée : <span class="font-medium text-slate-800">{{ number_format($item->superficie_validee) }} ha</span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div class="bg-blue-600 h-full rounded-full" style="width: {{ min($ratio, 100) }}%"></div>
                </div>
            </div>
        @empty
            <div class="col-span-2 py-4 text-center">
                <p class="text-xs text-slate-400 italic">Aucune donnée régionale disponible.</p>
            </div>
        @endforelse
    </div>
</div>