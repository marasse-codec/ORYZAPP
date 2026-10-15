{{--
    Barre de filtres des statistiques. Paramètres : $action (URL du formulaire),
    $filtre (FiltreStatistique), les listes ($departements, $localites, $typesAppuis,
    $typesActeurs) et, en option, $avecCampagne + $campagnes + $campagne pour choisir la campagne,
    ou $campagnesACocher (ids) pour cocher plusieurs campagnes à comparer.
    Un filtre vide est ignoré : « Tous » = pas de restriction.
--}}
@php $p = $filtre?->parametres() ?? []; @endphp
<x-ui.card titre="Filtres">
    <form method="GET" action="{{ $action }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @if ($avecCampagne ?? false)
            <label class="text-sm">
                <span class="text-[--color-muted]">Campagne</span>
                <select name="campagne" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($campagnes as $option)
                        <option value="{{ $option->id_campagne }}" @selected($campagne && $option->is($campagne))>
                            {{ $option->lib_campagne }} ({{ $option->mois_debut_campagne->format('m/Y') }} - {{ $option->mois_fin_campagne->format('m/Y') }}){{ $option->estCloturee() ? ' · clôturée' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        @foreach ([
            ['departement', 'Département', $departements, 'id_departement', 'lib_departement'],
            ['localite', 'Localité', $localites, 'id_localite', 'lib_localite'],
            ['type_appui', "Type d'appui", $typesAppuis, 'id_type_appui', 'lib_type_appui'],
            ['type_acteur', "Type d'acteur", $typesActeurs, 'id_type_acteur', 'lib_type_acteur'],
        ] as [$nom, $libelle, $options, $cle, $texte])
            <label class="text-sm">
                <span class="text-[--color-muted]">{{ $libelle }}</span>
                <select name="{{ $nom }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Tous</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->$cle }}" @selected(($p[$nom] ?? null) == $option->$cle)>{{ $option->$texte }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach

        <label class="text-sm">
            <span class="text-[--color-muted]">Soumises à partir du</span>
            <input type="date" name="du" value="{{ $p['du'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="text-sm">
            <span class="text-[--color-muted]">Jusqu'au</span>
            <input type="date" name="au" value="{{ $p['au'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>

        @isset($campagnesACocher)
            {{-- Comparaison : les campagnes à mettre côte à côte (les 5 plus récentes si rien n'est coché). --}}
            <fieldset class="text-sm sm:col-span-2 lg:col-span-4">
                <legend class="text-[--color-muted]">Campagnes comparées</legend>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    @foreach ($campagnes as $option)
                        <label class="inline-flex items-center gap-1.5">
                            <input type="checkbox" name="campagnes[]" value="{{ $option->id_campagne }}" @checked(in_array($option->id_campagne, $campagnesACocher)) class="rounded border-slate-300">
                            {{ $option->lib_campagne }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endisset

        <div class="flex items-end gap-2">
            <x-ui.bouton type="submit">Appliquer</x-ui.bouton>
            <x-ui.bouton :href="$action" variante="secondaire">Effacer</x-ui.bouton>
        </div>
    </form>
</x-ui.card>
