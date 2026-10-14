{{--
    Fiches d'arbitrage : un brouillon papier par demande. La commission y note, en séance, les superficies
    qu'elle peut traiter et les quantités accordées ; l'agent de commission les saisit ensuite dans l'écran
    d'attribution. Les cadres à remplir sont donc volontairement vides.
--}}
@extends('layouts.impression', ['retour' => route('editions.arbitrages.index')])

@section('titre', "Fiche d'arbitrage")
@section('sous-titre', $demandes->count().' fiche(s)')

@section('document')
    @forelse ($demandes as $demande)
        @php
            $numero = 'DEM-'.str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT);
            $catalogue = $catalogues[$demande->id_demande];
        @endphp

        <article class="{{ $loop->last ? '' : 'mb-10' }}" style="{{ $loop->last ? '' : 'break-after: page;' }}">

            <div class="mt-4 flex items-center justify-between border-b border-slate-300 pb-2">
                <p class="text-base font-bold uppercase">Fiche d'arbitrage {{ $numero }}</p>
                <p class="text-xs text-slate-600">Séance du ____ / ____ / ________</p>
            </div>

            <section class="insecable mt-4">
                <h2 class="mb-2 text-xs font-bold uppercase text-[--color-primary]">1. Demandeur et demande</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs">
                    <div><dt class="inline text-slate-500">Demandeur :</dt> <dd class="inline font-semibold">{{ $demande->nom_structure ?? $demande->acteur?->nom ?? 'N/A' }}</dd></div>
                    <div><dt class="inline text-slate-500">Type d'acteur :</dt> <dd class="inline font-semibold">{{ $demande->typeActeur?->lib_type_acteur ?? '-' }}</dd></div>
                    <div><dt class="inline text-slate-500">Campagne :</dt> <dd class="inline font-semibold">{{ $demande->campagne?->lib_campagne ?? '-' }}</dd></div>
                    <div><dt class="inline text-slate-500">Soumise le :</dt> <dd class="inline font-semibold">{{ $demande->date_soummission?->format('d/m/Y') ?? '-' }}</dd></div>
                    <div class="col-span-2"><dt class="inline text-slate-500">Localité(s) :</dt> <dd class="inline font-semibold">{{ $demande->localites->pluck('lib_localite')->join(', ') ?: 'Non spécifiée' }}</dd></div>
                    @if ($demande->observations)
                        <div class="col-span-2"><dt class="inline text-slate-500">Observations de visite :</dt> <dd class="inline">{{ $demande->observations }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="insecable mt-4">
                <h2 class="mb-2 text-xs font-bold uppercase text-[--color-primary]">2. Superficies par écologie</h2>
                <table class="w-full border-collapse border border-slate-400 text-xs">
                    <thead class="bg-slate-100 text-left text-[10px] uppercase">
                        <tr>
                            <th class="border border-slate-400 p-1.5">Écologie</th>
                            <th class="border border-slate-400 p-1.5 text-right">Demandée (ha)</th>
                            <th class="w-40 border border-slate-400 p-1.5 text-right">Traitable (ha)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demande->systemesProductions as $systeme)
                            <tr>
                                <td class="border border-slate-400 p-1.5">{{ $systeme->typeSystemeProduction?->lib_type_systeme_production }}</td>
                                <td class="border border-slate-400 p-1.5 text-right">{{ number_format($systeme->superficie, 2, ',', ' ') }}</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="border border-slate-400 p-2 text-center italic text-slate-500">Aucune superficie déclarée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="insecable mt-4">
                <h2 class="mb-2 text-xs font-bold uppercase text-[--color-primary]">3. Appuis sollicités</h2>
                <table class="w-full border-collapse border border-slate-400 text-xs">
                    <thead class="bg-slate-100 text-left text-[10px] uppercase">
                        <tr>
                            <th class="border border-slate-400 p-1.5">Type d'appui</th>
                            <th class="border border-slate-400 p-1.5">Appui visé</th>
                            <th class="border border-slate-400 p-1.5 text-right">Qté demandée</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demande->lignes as $ligne)
                            <tr>
                                <td class="border border-slate-400 p-1.5">{{ $ligne->typeAppui?->lib_type_appui }}</td>
                                <td class="border border-slate-400 p-1.5">{{ $ligne->appui?->libelle ?? '-' }}</td>
                                <td class="border border-slate-400 p-1.5 text-right">
                                    @if ($ligne->quantite_demandee !== null)
                                        {{ \App\Utils\Helper::quantite($ligne->quantite_demandee) }} {{ $ligne->appui?->uniteDosage?->lib_unite }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="insecable mt-4">
                <h2 class="mb-2 text-xs font-bold uppercase text-[--color-primary]">4. Attribution (à remplir)</h2>
                <table class="w-full border-collapse border border-slate-400 text-xs">
                    <thead class="bg-slate-100 text-left text-[10px] uppercase">
                        <tr>
                            <th class="border border-slate-400 p-1.5">Appui</th>
                            <th class="border border-slate-400 p-1.5 text-center">Unité</th>
                            <th class="border border-slate-400 p-1.5 text-right">Disponible</th>
                            <th class="w-28 border border-slate-400 p-1.5 text-right">Qté accordée</th>
                            <th class="w-20 border border-slate-400 p-1.5 text-right">Remise %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($catalogue as $appui)
                            <tr>
                                <td class="border border-slate-400 p-1.5">{{ $appui['libelle'] }}</td>
                                <td class="border border-slate-400 p-1.5 text-center">{{ $appui['unite'] ?? '-' }}</td>
                                <td class="border border-slate-400 p-1.5 text-right {{ $appui['disponible'] < $appui['demandee'] ? 'font-bold' : '' }}">{{ \App\Utils\Helper::quantite($appui['disponible']) }}</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                            </tr>
                        @endforeach
                        {{-- Lignes libres : un appui qui n'est pas au catalogue du dossier. --}}
                        @for ($i = 0; $i < 2; $i++)
                            <tr class="h-7">
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                                <td class="border border-slate-400 p-1.5">&nbsp;</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                <p class="mt-1 text-[10px] italic text-slate-500">« Disponible » = stock moins ce qui est déjà engagé par d'autres attributions ; en gras quand il ne couvre pas la demande.</p>
            </section>

            <section class="insecable mt-4 border border-slate-400 p-3">
                <h2 class="mb-2 text-xs font-bold uppercase text-[--color-primary]">5. Décision et observations</h2>
                <p class="mb-3 flex gap-6 text-xs">
                    <span class="font-semibold">Avis :</span>
                    <span>☐ Favorable</span>
                    <span>☐ Ajourné</span>
                    <span>☐ Rejeté</span>
                </p>
                <p class="text-xs text-slate-500">Observations et motivations :</p>
                <div class="h-20"></div>
            </section>

            <section class="insecable mt-6 grid grid-cols-3 gap-4 text-center text-xs">
                @foreach (['Le rapporteur', 'Service technique ADERIZ', 'Le président de la commission'] as $signataire)
                    <div>
                        <p class="font-semibold">{{ $signataire }}</p>
                        <div class="h-16"></div>
                        <p class="text-[10px] text-slate-400">Nom et signature</p>
                    </div>
                @endforeach
            </section>
        </article>
    @empty
        <p class="mt-10 text-center text-sm text-slate-500">Aucune demande n'attend l'arbitrage : rien à imprimer.</p>
    @endforelse
@endsection
