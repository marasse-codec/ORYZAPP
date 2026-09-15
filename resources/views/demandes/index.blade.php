@php
    use App\Enums\PermissionEnum;
@endphp

@extends($layout ?? 'layouts.app')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-6xl mx-auto">

        <!-- DP -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Demandes d'appui</h1>
                <p class="text-sm text-slate-500">{{ $demandes->count() }} dossier(s)</p>
            </div>
            @can(\App\Enums\PermissionEnum::creerDemande->value)
                <a href="{{ route('demandes.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-md bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                    <span class="material-symbols-outlined text-base">add</span> Nouvelle demande
                </a>
            @endcan
        </div>

        @include('partials.flash')

        {{-- Attributeur --}}
        @can(\App\Enums\PermissionEnum::gererCommission->value)
            <section class="mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-[--color-primary]">
                            À traiter en commission
                        </h2>
                        <p class="text-sm text-slate-500">
                            {{ $dossiersCommission->count() }} dossier(s) instruit(s) favorablement,
                            en attente d'arbitrage.
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
                    <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        Aucun dossier n'attend d'arbitrage pour le moment.
                    </p>
                @else
                    {{-- Confrontation demandé / disponible, tous dossiers confondus --}}
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">Appui</th>
                                    <th class="px-4 py-2.5 text-center">Dossiers</th>
                                    <th class="px-4 py-2.5 text-right">Demandé</th>
                                    <th class="px-4 py-2.5 text-right">En stock</th>
                                    <th class="px-4 py-2.5 text-right">Engagé</th>
                                    <th class="px-4 py-2.5 text-right">Disponible</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($confrontation as $ligne)
                                    <tr class="{{ $ligne['couverte'] ? '' : 'bg-red-50/60' }}">
                                        <td class="px-4 py-2.5">
                                            <span class="font-medium text-slate-800">{{ $ligne['libelle'] }}</span>
                                            <span class="block text-xs text-slate-500">{{ $ligne['service'] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-slate-600">{{ $ligne['nb_dossiers'] }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-800">
                                            {{ $ligne['demandee'] }}
                                            <span class="font-normal text-slate-500">{{ $ligne['unite'] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-slate-600">{{ $ligne['stock'] }}</td>
                                        <td class="px-4 py-2.5 text-right text-slate-600">{{ $ligne['engagee'] }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="font-bold {{ $ligne['couverte'] ? 'text-emerald-700' : 'text-red-700' }}">
                                                {{ $ligne['disponible'] }}
                                            </span>
                                            @unless ($ligne['couverte'])
                                                <span class="block text-xs text-red-700">
                                                    manque {{ $ligne['demandee'] - $ligne['disponible'] }}
                                                </span>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">
                        « Engagé » : quantités déjà attribuées sur des bons non encore retirés au magasin,
                        donc toujours comptées dans le stock. Disponible = stock − engagé.
                    </p>

                    {{-- Les dossiers concernés, avec accès direct à l'arbitrage --}}
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($dossiersCommission as $dossier)
                            <a href="{{ route('attributions.create', $dossier->id_demande) }}"
                               class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-[--color-primary] hover:text-[--color-primary]">
                                DEM-{{ $dossier->date_demande?->format('Y') }}-{{ str_pad($dossier->id_demande, 3, '0', STR_PAD_LEFT) }}
                                — {{ $dossier->acteur?->nom }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endcan

        {{-- Tous --}}
        {{-- La liste complète --}}
        @if ($demandes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <p class="text-sm text-slate-500">Aucune demande à afficher pour le moment.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Dossier</th>
                            <th class="px-4 py-3">Demandeur</th>
                            <th class="px-4 py-3">Profil</th>
                            <th class="px-4 py-3">Localité</th>
                            <th class="px-4 py-3 text-center">Appuis</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($demandes as $demande)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-slate-800">
                                        DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="block text-xs text-slate-500">{{ $demande->date_demande?->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ $demande->acteur?->nom }}
                                    <span class="block text-xs text-slate-500">{{ $demande->acteur?->telephone }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $demande->typeActeur?->lib_type_acteur }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $demande->localite?->lib_localite }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $demande->lignes->count() }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $demande->id_statut_demande->couleur() }}">
                                        {{ $demande->id_statut_demande->libelle() }}
                                    </span>
                                    @if ($demande->motifs_rejet)
                                        <span class="mt-1 block text-xs text-red-700">{{ $demande->motifs_rejet }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">

                                        <a href="{{ route('demandes.show', $demande->id_demande) }}"
                                        class="text-sm font-medium text-[--color-secondary] hover:underline">
                                            Consulter
                                        </a>

                                        @can(PermissionEnum::attribuerAppui->value)
                                            @if($demande->traitement_terminee === false)
                                            <a href="{{ route('attributions.create', $demande->id_demande) }}"
                                            class="text-sm font-medium text-[--color-primary] hover:underline">
                                                Attribuer
                                            </a>
                                            @endif
                                        @endcan

                                        @can(PermissionEnum::creerDemande->value)

                                            @if($demande->estModifiable())

                                                <a href="{{ route('demandes.edit', $demande->id_demande) }}"
                                                class="text-sm font-medium text-yellow-700 hover:underline">
                                                    Modifier
                                                </a>

                                                <form action="{{ route('demandes.soumettre', $demande->id_demande) }}"
                                                    method="POST"
                                                    class="inline">
                                                    @csrf

                                                    <button type="submit"
                                                            class="text-sm font-semibold text-emerald-700 hover:underline"
                                                            onclick="return confirm('Voulez-vous confirmer la soumission de cette demande ?')">
                                                        Soumettre
                                                    </button>
                                                </form>

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
