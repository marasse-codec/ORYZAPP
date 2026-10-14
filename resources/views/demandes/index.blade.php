@use('App\Domain\Acces\Enums\PermissionEnum')
@use('App\Domain\Demande\Enums\StatutDemandeE')

@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Demandes')

@section('content')
<x-ui.page titre="Demandes d'appui" :sous-titre="$demandes->total().' dossier(s)'">
    @can(PermissionEnum::creerDemande->value)
        <x-slot:actions>
            <x-ui.bouton :href="route('demandes.serie.create')" variante="secondaire">Saisie en série</x-ui.bouton>
            <x-ui.bouton :href="route('demandes.create')">
                <span class="material-symbols-outlined text-base">add</span> Nouvelle demande
            </x-ui.bouton>
        </x-slot:actions>
    @endcan

    {{-- Arbitrage de la commission : ce qui est demandé face à ce qui reste disponible, appui par appui. --}}
    @can(PermissionEnum::gererCommission->value)
        <x-ui.card titre="À traiter en commission">
            <x-slot:entete>
                @if ($dossiersCommission->isNotEmpty())
                    <x-ui.bouton :href="route('commissions.synthese')" variante="secondaire" target="_blank" class="px-3 py-1.5 text-xs">
                        <span class="material-symbols-outlined text-sm">print</span> Imprimer la synthèse
                    </x-ui.bouton>
                @endif
            </x-slot:entete>

            <p class="mb-4 text-sm text-[--color-muted]">{{ $dossiersCommission->count() }} dossier(s) soumis, en attente d'arbitrage.</p>

            @if ($confrontation->isEmpty())
                <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-[--color-muted]">
                    {{ $dossiersCommission->isEmpty()
                        ? "Aucun dossier n'attend d'arbitrage pour le moment."
                        : "Les dossiers en attente ne précisent aucun appui : il n'y a rien à confronter aux stocks." }}
                </p>
            @else
                <div class="overflow-hidden rounded-lg border border-[--color-border]">
                    <x-ui.tableau>
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                            <tr>
                                <th class="px-4 py-2.5">Appui</th>
                                <th class="px-4 py-2.5 text-center">Dossiers</th>
                                <th class="px-4 py-2.5 text-right">Demandé</th>
                                <th class="px-4 py-2.5 text-right">En stock</th>
                                <th class="px-4 py-2.5 text-right">Engagé</th>
                                <th class="px-4 py-2.5 text-right">Disponible</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[--color-border]">
                            @foreach ($confrontation as $ligne)
                                <tr @class(['bg-rose-50/60' => ! $ligne['couverte']])>
                                    <td class="px-4 py-2.5">
                                        <span class="font-medium text-[--color-ink]">{{ $ligne['libelle'] }}</span>
                                        <span class="block text-xs text-[--color-muted]">{{ $ligne['service'] }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-slate-600">{{ $ligne['nb_dossiers'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-[--color-ink]">
                                        {{ $ligne['demandee'] }} <span class="font-normal text-[--color-muted]">{{ $ligne['unite'] }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-slate-600">{{ $ligne['stock'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-600">{{ \App\Utils\Helper::quantite($ligne['engagee']) }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span @class(['font-bold', 'text-[--color-primary]' => $ligne['couverte'], 'text-rose-600' => ! $ligne['couverte']])>{{ \App\Utils\Helper::quantite($ligne['disponible']) }}</span>
                                        @unless ($ligne['couverte'])
                                            <span class="block text-xs text-rose-600">manque {{ \App\Utils\Helper::quantite($ligne['demandee'] - $ligne['disponible']) }}</span>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.tableau>
                </div>
            @endif
        </x-ui.card>
    @endcan

    {{-- Recherche et filtre par statut : un formulaire GET, sans JavaScript. --}}
    <x-ui.card>
        <form method="GET" action="{{ route('demandes.index') }}" class="grid gap-3 sm:grid-cols-[1fr_14rem_auto] sm:items-end">
            <div>
                <x-input-label for="q" value="Rechercher" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$recherche" placeholder="N°, entité, représentant ou téléphone…" />
            </div>
            <div>
                <x-input-label for="statut" value="Statut" />
                <select id="statut" name="statut" class="mt-1 block w-full">
                    <option value="">Tous les statuts</option>
                    @foreach (StatutDemandeE::cases() as $cas)
                        <option value="{{ $cas->value }}" @selected($statut === $cas)>{{ $cas->libelle() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.bouton type="submit" variante="secondaire">Filtrer</x-ui.bouton>
                @if ($recherche !== '' || $statut)
                    <x-ui.bouton :href="route('demandes.index')" variante="discret">Effacer</x-ui.bouton>
                @endif
            </div>
        </form>
    </x-ui.card>

    @if ($demandes->isEmpty())
        <x-ui.card>
            <p class="py-6 text-center text-sm text-[--color-muted]">Aucune demande à afficher.</p>
        </x-ui.card>
    @else
        <x-ui.card sans-marge>
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3">N°</th>
                        <th class="px-4 py-3">Entité</th>
                        <th class="px-4 py-3">Localité</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3 text-center">Statut</th>
                        <th class="px-4 py-3">Observations</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($demandes as $demande)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-[--color-ink]">{{ $demande->id_demande }}</td>
                            <td class="px-4 py-3">
                                <span class="block font-semibold text-[--color-primary]">{{ $demande->nom_structure ?? $demande->acteur?->nom }}</span>
                                @if ($demande->nom_structure && $demande->acteur?->nom)
                                    <span class="text-xs text-[--color-muted]">Rep : {{ $demande->acteur->nom }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $demande->localites->pluck('lib_localite')->join(', ') ?: '-' }}
                                @if ($departements = $demande->localites->pluck('departement.lib_departement')->filter()->unique()->join(', '))
                                    <span class="block text-xs text-[--color-muted]">{{ $departements }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $demande->acteur?->telephone ?? '-' }}
                                @if ($demande->acteur?->email)
                                    <span class="block break-all text-xs text-[--color-muted]">{{ $demande->acteur->email }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <x-ui.badge :ton="$demande->statut->ton()">{{ $demande->statut->libelle() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $demande->observations }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                    <a href="{{ route('demandes.show', $demande->id_demande) }}" class="text-sm font-medium text-[--color-primary] hover:underline">Consulter</a>

                                    @can(PermissionEnum::creerDemande->value)
                                        @unless ($demande->estSoumise())
                                            <a href="{{ route('demandes.edit', $demande->id_demande) }}" class="text-sm font-medium text-[--color-button-secondary] hover:underline">Modifier</a>

                                            @if ($demande->terrainAEteVisite())
                                                <form action="{{ route('demandes.soumettre', $demande->id_demande) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-sm font-medium text-[--color-primary] hover:underline"
                                                            onclick="return confirm('Confirmer la soumission ?')">Soumettre</button>
                                                </form>
                                            @else
                                                <x-modals.visite-terrain :id="$demande->id_demande"
                                                                         :action="route('demandes.visite.valider')"
                                                                         :title="'Visite de terrain - Demande n°'.$demande->id_demande" />
                                            @endif
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        </x-ui.card>

        {{ $demandes->links() }}
    @endif
</x-ui.page>
@endsection
