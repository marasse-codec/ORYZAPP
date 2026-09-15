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