@use('App\Enums\PermissionEnum')
@use('App\Enums\RoleEnum')
@use('Illuminate\Support\Str')

@extends($layout ?? 'layouts.app')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- En-tête de page --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Demandes d'appui</h1>
                <p class="text-sm text-[--color-text-secondary]">{{ $demandes->count() }} dossier(s) enregistré(s)</p>
            </div>
            @can(PermissionEnum::creerDemande->value)
                <a href="{{ route('demandes.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-md bg-[--color-button-first] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[--color-button-secondary] transition-colors">
                    <span class="material-symbols-outlined text-base">add</span> Nouvelle demande
                </a>
            @endcan
        </div>

        @include('partials.flash')

        {{-- Section Arbitrage Commission (Attributeur) --}}
        @can(PermissionEnum::gererCommission->value)
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-[--color-primary]">
                            À traiter en commission
                        </h2>
                        <p class="text-sm text-[--color-text-secondary]">
                            {{ $dossiersCommission->count() }} dossier(s) instruit(s) favorablement, en attente d'arbitrage.
                        </p>
                    </div>
                    @if ($dossiersCommission->isNotEmpty())
                        <a href="{{ route('commissions.synthese') }}" target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-md border border-[--color-primary] px-4 py-2 text-sm font-semibold text-[--color-primary] hover:bg-emerald-50">
                            <span class="material-symbols-outlined text-base">print</span>
                            Imprimer la synthèse
                        </a>
                    @endif
                </div>

                @if ($confrontation->isEmpty())
                    <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-[--color-text-secondary]">
                        Aucun dossier n'attend d'arbitrage pour le moment.
                    </p>
                @else
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-text-secondary]">
                                <tr>
                                    <th class="px-4 py-2.5">Appui</th>
                                    <th class="px-4 py-2.5 text-center">Dossiers</th>
                                    <th class="px-4 py-2.5 text-right">Demandé</th>
                                    <th class="px-4 py-2.5 text-right">En stock</th>
                                    <th class="px-4 py-2.5 text-right">Engagé</th>
                                    <th class="px-4 py-2.5 text-right">Disponible</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($confrontation as $ligne)
                                    <tr class="{{ $ligne['couverte'] ? '' : 'bg-[--color-accent]/10' }}">
                                        <td class="px-4 py-2.5">
                                            <span class="font-medium text-[--color-text-primary]">{{ $ligne['libelle'] }}</span>
                                            <span class="block text-xs text-[--color-text-secondary]">{{ $ligne['service'] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-[--color-text-secondary]">{{ $ligne['nb_dossiers'] }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-[--color-text-primary]">
                                            {{ $ligne['demandee'] }}
                                            <span class="font-normal text-[--color-text-secondary]">{{ $ligne['unite'] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-[--color-text-secondary]">{{ $ligne['stock'] }}</td>
                                        <td class="px-4 py-2.5 text-right text-[--color-text-secondary]">{{ $ligne['engagee'] }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="font-bold {{ $ligne['couverte'] ? 'text-[--color-primary]' : 'text-[--color-accent]' }}">
                                                {{ $ligne['disponible'] }}
                                            </span>
                                            @unless ($ligne['couverte'])
                                                <span class="block text-xs text-[--color-accent]">
                                                    manque {{ $ligne['demandee'] - $ligne['disponible'] }}
                                                </span>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endcan

        {{-- Tableau avec les colonnes spécifiées --}}
        @if ($demandes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-sm text-[--color-text-secondary]">Aucune demande à afficher pour le moment.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                    <thead class="bg-slate-100 uppercase tracking-wider font-semibold text-[--color-primary]">
                        <tr>
                            <th class="px-3 py-3">Num</th>
                            <th class="px-3 py-3">Département</th>
                            <th class="px-3 py-3">Localité</th>
                            <th class="px-3 py-3">Nom de l'entité</th>
                            <th class="px-3 py-3">Contacts</th>
                            {{-- <th class="px-3 py-3 whitespace-nowrap">Date d'enregistrement</th>
                            <th class="px-3 py-3">Source</th> --}}
                            {{-- <th class="px-3 py-3 text-right whitespace-nowrap">Sup en plat (ha)</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">Sup en BF pluv (ha)</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">Sup en irri (ha)</th> --}}
                            <th class="px-3 py-3">Statut</th>
                            <th class="px-3 py-3">Observations</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-[--color-text-primary]">
                        @foreach ($demandes as $demande)
                            @php
                                // Extraction des superficies par système
                                $supPlat = $demande->systemesProductions->first(
                                    fn($s) => Str::contains(mb_strtolower($s->typeSystemeProduction?->lib_type_systeme_production ?? ''), ['plat', 'plateau'])
                                )?->superficie;

                                $supBfPluv = $demande->systemesProductions->first(
                                    fn($s) => Str::contains(mb_strtolower($s->typeSystemeProduction?->lib_type_systeme_production ?? ''), ['bf', 'bas-fond', 'pluvial'])
                                )?->superficie;

                                $supIrri = $demande->systemesProductions->first(
                                    fn($s) => Str::contains(mb_strtolower($s->typeSystemeProduction?->lib_type_systeme_production ?? ''), ['irri', 'irrigué'])
                                )?->superficie;

                                // Départements uniques
                                $departements = $demande->localites->pluck('departement.lib_departement')->filter()->unique()->join(', ');
                                // Localités uniques
                                $localitesStr = $demande->localites->pluck('lib_localite')->join(', ');
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">

                                {{-- O. Demande --}}
                                <td class="px-3 py-3 font-medium">
                                    {{ $demande -> id_demande ?: '—' }}
                                </td>

                                {{-- 1. Département --}}
                                <td class="px-3 py-3 font-medium">
                                    {{ $departements ?: '—' }}
                                </td>

                                {{-- 2. Localité --}}
                                <td class="px-3 py-3">
                                    {{ $localitesStr ?: '—' }}
                                </td>

                                {{-- 3. Nom de l'entité --}}
                                <td class="px-3 py-3">
                                    <span class="font-semibold text-[--color-primary] block">
                                        {{ $demande->nom_structure ?? $demande->acteur?->nom }}
                                    </span>
                                    @if ($demande->nom_structure && $demande->acteur?->nom)
                                        <span class="text-[10px] text-[--color-text-secondary]">Rep : {{ $demande->acteur->nom }}</span>
                                    @endif
                                </td>

                                {{-- 4. Contacts --}}
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div>{{ $demande->acteur?->telephone ?? '—' }}</div>
                                    @if ($demande->acteur?->email)
                                        <div class="text-[10px] text-[--color-text-secondary]">{{ $demande->acteur->email }}</div>
                                    @endif
                                </td>

                                {{-- 5. Date d'enregistrement --}}
                                {{-- <td class="px-3 py-3 whitespace-nowrap">
                                    {{ $demande->date_demande?->format('d/m/Y') ?? '—' }}
                                </td> --}}

                                {{-- 6. Source --}}
                                {{-- <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                        {{ $demande->user?->name ?? 'Agent' }}
                                    </span>
                                </td> --}}

                                {{-- 7. Sup en plat (ha)
                                <td class="px-3 py-3 text-right font-medium whitespace-nowrap">
                                    {{ $supPlat !== null ? number_format($supPlat, 2, ',', ' ') : '—' }}
                                </td>

                                {{-- 8. Sup en BF pluv (ha) --}}
                                {{-- <td class="px-3 py-3 text-right font-medium whitespace-nowrap">
                                    {{ $supBfPluv !== null ? number_format($supBfPluv, 2, ',', ' ') : '—' }}
                                </td> --}}

                                {{-- 9. Sup en irri (ha) --}}
                                {{-- <td class="px-3 py-3 text-right font-medium whitespace-nowrap">
                                    {{ $supIrri !== null ? number_format($supIrri, 2, ',', ' ') : '—' }}
                                </td> --}}

                                {{-- 10. Statut --}}
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-block rounded-full px-2 py-0.5 text-[8px] font-semibold text-[--text-in-back-color] {{ $demande->estSoumise() ? 'bg-[--color-soumise]' : 'bg-[--color-label-brouillon]' }}">
                                            {{ $demande->estSoumise() ? 'Soumise' : 'En cours' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- 11. Observations --}}
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-block rounded-full px-2 py-0.5 text-[12px] font-semibold">
                                            {{$demande?->observations}}
                                        </span>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('demandes.show', $demande->id_demande) }}"
                                           class="text-xs font-semibold text-[--color-primary] hover:underline">
                                            Consulter
                                        </a>

                                        @can(PermissionEnum::creerDemande->value)
                                            @if(!$demande->estSoumise())
                                            
                                            {{--Modifier--}}
                                                <a href="{{ route('demandes.edit', $demande->id_demande) }}"
                                                   class="text-xs font-semibold text-[--color-button-first] hover:underline">
                                                    Modifier
                                                </a>

                                            @if($demande->terrainAEteVisite())
                                            {{--Soumettre--}}
                                                <form action="{{ route('demandes.soumettre', $demande->id_demande) }}"
                                                      method="POST"
                                                      class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-xs font-semibold text-[--color-primary] hover:underline"
                                                            onclick="return confirm('Confirmer la soumission ?')">
                                                        Soumettre
                                                    </button>
                                                </form>
                                            @else
                                            {{-- Appel du composant réutilisable --}}
                                                <x-modals.visite-terrain 
                                                    :id="$demande->id_demande" 
                                                    :action="route('demandes.visite.valider')" 
                                                    :title="'Visite de terrain - Demande n°' . $demande->id_demande" 
                                                />
                                            @endif
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection