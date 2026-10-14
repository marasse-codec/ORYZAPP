@extends('layouts.impression', ['retour' => route('demandes.index')])

@section('sur-titre', 'Document de séance')
@section('titre', 'Synthèse de commission')
@section('sous-titre', $dossiers->count().' dossier(s) à arbitrer')

@section('document')

    {{-- 1. La confrontation globale : c'est elle qui cadre l'arbitrage --}}
    <section class="mt-6 insecable">
        <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">
            Quantités demandées et disponibilités
        </h2>

        @if ($confrontation->isEmpty())
            <p class="mt-2 text-sm text-slate-500">Aucun dossier n'attend d'arbitrage.</p>
        @else
            <table class="mt-2 w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-300 text-left text-xs uppercase text-slate-500">
                        <th class="pb-1">Appui</th>
                        <th class="pb-1 text-center">Dossiers</th>
                        <th class="pb-1 text-right">Demandé</th>
                        <th class="pb-1 text-right">En stock</th>
                        <th class="pb-1 text-right">Engagé</th>
                        <th class="pb-1 text-right">Disponible</th>
                        <th class="pb-1 text-right w-28">Arbitrage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($confrontation as $ligne)
                        <tr>
                            <td class="py-1.5 text-slate-800">
                                {{ $ligne['libelle'] }}
                                <span class="block text-[10px] text-slate-500">{{ $ligne['service'] }}</span>
                            </td>
                            <td class="py-1.5 text-center text-slate-600">{{ $ligne['nb_dossiers'] }}</td>
                            <td class="py-1.5 text-right font-medium text-slate-800">
                                {{ $ligne['demandee'] }} {{ $ligne['unite'] }}
                            </td>
                            <td class="py-1.5 text-right text-slate-600">{{ $ligne['stock'] }}</td>
                            <td class="py-1.5 text-right text-slate-600">{{ $ligne['engagee'] }}</td>
                            <td class="py-1.5 text-right font-bold {{ $ligne['couverte'] ? 'text-slate-800' : 'text-red-700' }}">
                                {{ $ligne['disponible'] }}
                                @unless ($ligne['couverte'])
                                    <span class="block text-[10px] font-normal">insuffisant</span>
                                @endunless
                            </td>
                            {{-- Colonne blanche : la commission y note sa décision au stylo. --}}
                            <td class="py-1.5"><span class="block border-b border-dotted border-slate-400">&nbsp;</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="mt-2 text-[10px] text-slate-500">
                « Engagé » : quantités déjà attribuées sur des bons non encore retirés au magasin, donc
                toujours comptées dans le stock. « Disponible » = stock − engagé.
            </p>
        @endif
    </section>

    {{-- 2. Le détail dossier par dossier, pour l'examen en séance --}}
    <section class="mt-8">
        <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">
            Dossiers à examiner
        </h2>

        @php
            $demandesDistinctes = $dossiers
                ->groupBy('id_demande') // 1. On groupe les attributions par demande
                ->map(function ($attributionsDeLaDemande) {
                    // 2. On récupère l'objet Demande depuis la première attribution du groupe
                    $demande = $attributionsDeLaDemande->first()->demande;
                    
                    if ($demande) {
                        // 3. Défi réussi : On injecte la collection de TOUTES ses attributions
                        // dans une propriété dynamique de la demande
                        $demande->attributions = $attributionsDeLaDemande;
                    }
                    
                    return $demande;
                })
                ->filter(); // 4. Sécurité au cas où un id_demande ne pointe sur rien

        @endphp

        @forelse ($demandesDistinctes as $demande)
            <article class="mt-4 insecable border border-slate-300 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-bold text-slate-800">
                            DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                            : {{ $demande->acteur?->nom }}
                        </p>
                        <p class="text-xs text-slate-600">
                            {{ $demande->typeActeur?->lib_type_acteur }},
                            {{ $demande->localite?->lib_localite }}
                            ({{ $demande->localite?->departement?->lib_departement }}),
                            {{ number_format($demande->systemesProductions?->sum('superficie'), 2, ',', ' ') }} ha,
                            {{ $demande->acteur?->telephone }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <p>Debut mise en place</p>
                        <p class="font-semibold text-slate-700">
                            {{ $demande->periode_debut_mise_en_place?->format('d/m/Y') }}
                        </p>
                        <p>Fin mise en place</p>
                        <p class="font-semibold text-slate-700">
                            {{ $demande->periode_fin_mise_en_place?->format('d/m/Y') }}
                        </p>
                    </div>
                </div>

                <table class="mt-3 w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-[10px] uppercase text-slate-500">
                            <th class="pb-1">Appui accordé</th>
                            <th class="pb-1 w-28">Quantité</th>
                            <th class="pb-1 w-28">Coût / remise</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($demande->attributions as $attribution)
                            @foreach ($attribution->lignesAttributions as $ligneAttrib)
                                <tr>
                                    <td class="py-1 text-slate-800"><span class="block border-b border-dotted border-slate-400">{{ $ligneAttrib->appui->libelle }}</span></td>
                                    <td class="py-1 text-slate-800"><span class="block border-b border-dotted border-slate-400">{{ $ligneAttrib->quantite_attribuee }}</span></td>
                                    <td class="py-1"><span class="block border-b border-dotted border-slate-400">{{ $ligneAttrib->remise }}</span></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3 flex items-center gap-6 text-xs text-slate-700">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block h-3 w-3 border border-slate-400"></span> Accordé
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block h-3 w-3 border border-slate-400"></span> Rejeté, motif :
                    </span>
                    <span class="flex-1 border-b border-dotted border-slate-400">&nbsp;</span>
                </div>
            </article>
        @empty
            <p class="mt-2 text-sm text-slate-500">Aucun dossier n'est prêt pour la commission.</p>
        @endforelse
    </section>

    {{-- 3. Émargement --}}
    <section class="mt-10 insecable">
        <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">Membres de la commission</h2>
        <table class="mt-2 w-full text-sm">
            <thead>
                <tr class="border-b border-slate-300 text-left text-xs uppercase text-slate-500">
                    <th class="pb-1 w-1/3">Nom et prénoms</th>
                    <th class="pb-1 w-1/3">Structure / service</th>
                    <th class="pb-1">Émargement</th>
                </tr>
            </thead>
            <tbody>
                @for ($ligne = 0; $ligne < 6; $ligne++)
                    <tr>
                        <td class="py-3 border-b border-dotted border-slate-300">&nbsp;</td>
                        <td class="py-3 border-b border-dotted border-slate-300">&nbsp;</td>
                        <td class="py-3 border-b border-dotted border-slate-300">&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </section>
@endsection
