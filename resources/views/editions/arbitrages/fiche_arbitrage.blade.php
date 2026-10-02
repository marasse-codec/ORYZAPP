<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche d'Arbitrage - ADERIZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm;
            }
            body {
                background: white !important;
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-break {
                page-break-after: always;
                break-after: page;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans text-sm p-4 print:p-0">

    {{-- Barre d'outils (Masquée à l'impression) --}}
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-200 no-print">
        <div>
            <h1 class="font-bold text-slate-800 text-lg">Impression des Fiches d'Arbitrage</h1>
            <p class="text-xs text-slate-500">{{ $attributions->count() }} fiche(s) prête(s) à être imprimée(s)</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg text-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 000-4H9a2 2 0 000 4zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimer les fiches
            </button>
            <button onclick="window.close()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg text-sm transition">
                Fermer
            </button>
        </div>
    </div>

    {{-- Conteneur des Fiches --}}
    <div class="max-w-4xl mx-auto space-y-8 print:space-y-0">

        @forelse ($attributions as $attribution)
            @php
                $demande = $attribution->demande;
                $acteur = $demande?->acteur;
            @endphp

            <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 print:shadow-none print:border-0 print:p-0 page-break">
                
                {{-- En-tête Institutionnel --}}
                <div class="flex justify-between items-start border-b-2 border-emerald-700 pb-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-emerald-800 uppercase tracking-wide">ADERIZ</h2>
                        <p class="text-xs text-slate-500 uppercase font-semibold">Agence de Développement de la Filière Riz</p>
                        <p class="text-xs text-slate-400">Commission Nationale d'Arbitrage et d'Attribution</p>
                    </div>
                    <div class="text-right">
                        <div class="inline-block bg-slate-100 border border-slate-300 rounded-lg px-3 py-1 mb-1">
                            <span class="text-xs font-bold text-slate-700">FICHE D'ARBITRAGE</span>
                        </div>
                        <p class="text-xs text-slate-600 font-mono font-semibold">N° ATT-{{ str_pad($attribution->id_attribution, 4, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-xs text-slate-500">Date : {{ now()->format('d/m/Y') }}</p>
                    </div>
                </div>

                {{-- Section 1 : Informations sur la Demande --}}
                <div class="mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200 print:bg-slate-50/50">
                    <h3 class="text-xs font-bold uppercase text-emerald-800 border-b border-slate-200 pb-1 mb-3">
                        1. Identification du Demandeur & de la Demande
                    </h3>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                        <div>
                            <span class="text-slate-500">Réf. Demande :</span>
                            <span class="font-semibold text-slate-800 ml-1">DEM-{{ str_pad($demande?->id_demande ?? 0, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Date Demande :</span>
                            <span class="font-semibold text-slate-800 ml-1">{{ $demande?->created_at?->format('d/m/Y') ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Demandeur / Acteur :</span>
                            <span class="font-semibold text-slate-900 ml-1">{{ $acteur?->nom ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Type d'Acteur :</span>
                            <span class="font-semibold text-slate-800 ml-1">{{ $demande?->typeActeur?->libelle ?? '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-slate-500">Localité(s) :</span>
                            <span class="font-semibold text-slate-800 ml-1">
                                {{ $demande?->localites?->pluck('nom_localite')->join(', ') ?: 'Non spécifiée' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Section 2 : Tableau d'Arbitrage des Appuis --}}
                <div class="mb-6">
                    <h3 class="text-xs font-bold uppercase text-emerald-800 mb-3">
                        2. Éléments Soumis à l'Arbitrage
                    </h3>
                    <table class="w-full border-collapse border border-slate-300 text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 uppercase font-bold text-[10px] text-left">
                                <th class="border border-slate-300 p-2">N°</th>
                                <th class="border border-slate-300 p-2">Appui / Ressource</th>
                                <th class="border border-slate-300 p-2 text-center">Unité</th>
                                <th class="border border-slate-300 p-2 text-right">Qté Demandée</th>
                                <th class="border border-slate-300 p-2 text-right">Qté Accordée</th>
                                <th class="border border-slate-300 p-2 text-right">Montant Unitaire</th>
                                <th class="border border-slate-300 p-2 text-right">Total Estimé</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attribution->lignesAttributions as $index => $ligne)
                                <tr class="hover:bg-slate-50">
                                    <td class="border border-slate-300 p-2 text-center font-medium">{{ $index + 1 }}</td>
                                    <td class="border border-slate-300 p-2 font-medium text-slate-800">
                                        {{ $ligne->appui?->libelle ?? 'Appui non défini' }}
                                    </td>
                                    <td class="border border-slate-300 p-2 text-center text-slate-600">
                                        {{ $ligne->appui?->uniteAppui?->libelle ?? '-' }}
                                    </td>
                                    <td class="border border-slate-300 p-2 text-right text-slate-600">
                                        {{ number_format($ligne->quantite_demande ?? 0, 0, ',', ' ') }}
                                    </td>
                                    <td class="border border-slate-300 p-2 text-right font-bold text-emerald-800 bg-emerald-50/50">
                                        {{ number_format($ligne->quantite_accordee ?? 0, 0, ',', ' ') }}
                                    </td>
                                    <td class="border border-slate-300 p-2 text-right text-slate-700">
                                        {{ number_format($ligne->cout_unitaire ?? 0, 0, ',', ' ') }} F
                                    </td>
                                    <td class="border border-slate-300 p-2 text-right font-semibold text-slate-900">
                                        {{ number_format(($ligne->quantite_accordee ?? 0) * ($ligne->cout_unitaire ?? 0), 0, ',', ' ') }} F
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="border border-slate-300 p-4 text-center text-slate-500 italic">
                                        Aucune ligne d'appui enregistrée pour cette attribution.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 font-bold">
                                <td colspan="6" class="border border-slate-300 p-2 text-right uppercase text-[10px]">
                                    Montant Total Proposé :
                                </td>
                                <td class="border border-slate-300 p-2 text-right text-emerald-900 font-bold text-sm">
                                    {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F CFA
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Section 3 : Avis & Décision de la Commission d'Arbitrage --}}
                <div class="mb-8 border border-slate-300 rounded-lg p-4">
                    <h3 class="text-xs font-bold uppercase text-emerald-800 mb-2">
                        3. Décision & Observations de la Commission
                    </h3>
                    
                    <div class="flex items-center gap-6 mb-4 text-xs">
                        <span class="font-semibold text-slate-700">Avis de la Commission :</span>
                        <label class="inline-flex items-center gap-1">
                            <input type="checkbox" class="rounded border-slate-400">
                            <span>Favorable</span>
                        </label>
                        <label class="inline-flex items-center gap-1">
                            <input type="checkbox" class="rounded border-slate-400">
                            <span>Ajourné</span>
                        </label>
                        <label class="inline-flex items-center gap-1">
                            <input type="checkbox" class="rounded border-slate-400">
                            <span>Défavorable</span>
                        </label>
                    </div>

                    <div class="min-h-[60px] border border-dashed border-slate-300 rounded p-2 text-xs text-slate-400">
                        Observations / Recommandations :
                    </div>
                </div>

                {{-- Section 4 : Signatures --}}
                <div class="grid grid-cols-3 gap-4 text-center text-xs mt-12 pt-4 border-t border-slate-200">
                    <div>
                        <p class="font-bold text-slate-700">Le Rapporteur</p>
                        <div class="h-16"></div>
                        <p class="text-[10px] text-slate-400">Nom & Signature</p>
                    </div>
                    <div>
                        <p class="font-bold text-slate-700">Service Technique ADERIZ</p>
                        <div class="h-16"></div>
                        <p class="text-[10px] text-slate-400">Nom & Signature</p>
                    </div>
                    <div>
                        <p class="font-bold text-slate-700">Le Président de Commission</p>
                        <div class="h-16"></div>
                        <p class="text-[10px] text-slate-400">Nom & Signature</p>
                    </div>
                </div>

            </div>
        @empty
            <div class="bg-white p-12 text-center text-slate-500 rounded-xl shadow-sm border">
                Aucune attribution sélectionnée pour l'impression.
            </div>
        @endforelse

    </div>

</body>
</html>