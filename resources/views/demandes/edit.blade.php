@extends($layout ?? 'layouts.app')

@php
    // Pièces attendues par type d'acteur
    $piecesParTypeActeur = $typesActeurs->mapWithKeys(fn ($type) => [
        $type->id_type_acteur => $type->typesPieces->map(fn ($piece) => [
            'id' => $piece->id_type_piece,
            'libelle' => $piece->lib_type_piece,
            'obligatoire' => (bool) $piece->pivot->est_obligatoire,
        ])->values(),
    ]);

    // Récupération des lignes de demandes existantes
    $lignesDB = $demande->lignes->map(fn($ligne) => [
        'id_type_appui' => $ligne->id_type_appui ?? $ligne->type_appui->id_appui ?? '',
        'quantite_demandee' => $ligne->quantite_demandee ?? $ligne->quantite ?? 0,
    ])->toArray();

    $lignesInitiales = old('lignes', empty($lignesDB) ? [['id_type_appui' => '', 'quantite_demandee' => '']] : $lignesDB);

    // Récupération sécurisée des superficies existantes via la relation pivot
    $superficiesDB = [];
    if ($demande->relationLoaded('systemesProductions')) {
        foreach ($demande->systemesProductions as $sys) {
            $superficiesDB[$sys->id_type_systeme_production] = $sys->superficie ?? '';
        }
    }

    // Récupération de la localité
    $idLocalite = $demande->id_localite ?? optional($demande->localite)->id_localite ?? '';

    // Formatage strict des dates pour les champ <input type="date"> (Y-m-d)
    $debutMiseEnPlace = '';
    if (!empty($demande->periode_debut_mise_en_place)) {
        $debutMiseEnPlace = \Carbon\Carbon::parse($demande->periode_debut_mise_en_place)->format('Y-m-d');
    }

    $finMiseEnPlace = '';
    if (!empty($demande->periode_fin_mise_en_place)) {
        $finMiseEnPlace = \Carbon\Carbon::parse($demande->periode_fin_mise_en_place)->format('Y-m-d');
    }

    // Récupération de la campagne
    $idCampagne =  $demande->id_campagne ?? optional($demande->campagne)->id_campagne ?? '';

    // Récupération des pièces déjà téléversées
    $piecesExistantes = $demande->piecesDemandes->pluck('id_type_piece')->toArray();
@endphp

@section('content')
<div
    x-data="{
        step: 1,
        totalSteps: 4,
        idTypeActeur: '{{ old('id_type_acteur', $demande->id_type_acteur ?? optional($demande->acteur)->id_type_acteur ?? '') }}',
        lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
        piecesParTypeActeur: {{ Illuminate\Support\Js::from($piecesParTypeActeur) }},
        piecesExistantes: {{ Illuminate\Support\Js::from($piecesExistantes) }},

        get piecesAttendues() {
            return this.piecesParTypeActeur[this.idTypeActeur] ?? [];
        },

        infosAppui(id) {
            return this.appuisIndex[id] ?? null;
        },

        pieceDejaFournie(idPiece) {
            return this.piecesExistantes.includes(idPiece);
        },

        ajouterLigne() {
            this.lignes.push({ id_appui: '', quantite_demandee: '' });
        },

        retirerLigne(index) {
            if (this.lignes.length > 1) this.lignes.splice(index, 1);
        },

        suivant() { if (this.step < this.totalSteps) this.step++; },
        precedent() { if (this.step > 1) this.step--; },
    }"
    class="min-h-screen bg-[--color-background] text-slate-800 pb-16"
>
    <header class="bg-white border-b border-slate-200 px-6 py-5 shadow-sm">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">Modification de la demande n°{{ $demande->id_demande }}</h1>
                    <p class="text-xs text-slate-500">Agence pour le Développement de la Filière Riz (ADERIZ)</p>
                </div>
                <div class="text-xs font-semibold text-[--color-primary] bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full w-fit">
                    Étape <span x-text="step"></span> sur <span x-text="totalSteps"></span>
                </div>
            </div>

            <ol class="flex items-center gap-2">
                @foreach (['Profil', 'Demandeur', 'Demande', 'Appuis & pièces'] as $index => $libelle)
                    <li class="flex-1">
                        <div class="h-1.5 rounded-full transition-colors"
                             :class="step >= {{ $index + 1 }} ? 'bg-[--color-primary]' : 'bg-slate-200'"></div>
                        <span class="mt-1.5 block text-[11px] font-medium"
                              :class="step >= {{ $index + 1 }} ? 'text-[--color-primary]' : 'text-slate-400'">
                            {{ $index + 1 }}. {{ $libelle }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 mt-6">
        
        @if($demande->commentaire_rejet)
        <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-500 rounded text-amber-800">
            <p class="font-bold text-sm">Motif de renvoi / observation :</p>
            <p class="text-sm mt-1">{{ $demande->commentaire_rejet }}</p>
        </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">La demande n'a pas pu être mise à jour :</p>
                <ul class="mt-1.5 list-disc list-inside text-sm text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('demandes.update', $demande->id_demande) }}" enctype="multipart/form-data"
              class="bg-white rounded-xl shadow-sm border border-slate-200">
            @csrf
            @method('PUT')

            {{-- ÉTAPE 1 — Profil d'acteur ------------------------------------ --}}
            <section x-show="step === 1" x-cloak class="p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">1. Profil du demandeur</h2>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($typesActeurs as $typeActeur)
                        <label class="flex items-start gap-3 rounded-lg border p-4 cursor-pointer transition-colors"
                               :class="idTypeActeur === '{{ $typeActeur->id_type_acteur }}'
                                   ? 'border-[--color-primary] bg-emerald-50'
                                   : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="id_type_acteur" value="{{ $typeActeur->id_type_acteur }}"
                                   x-model="idTypeActeur" class="mt-1 text-[--color-primary] focus:ring-[--color-primary]">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">{{ $typeActeur->lib_type_acteur }}</span>
                                <span class="block text-xs text-slate-500">
                                    {{ $typeActeur->typesPieces->count() }} pièce(s) attendue(s)
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('id_type_acteur')" class="mt-1" />
                
                <div>
                    <h3 class="text-base font-semibold text-[--color-primary]">Pièces justificatives</h3>
                    
                    <div class="mt-3 space-y-4">
                        <template x-for="piece in piecesAttendues" :key="piece.id">
                            <div class="p-4 border border-slate-200 rounded-lg bg-slate-50">
                                <label class="block text-sm font-medium text-slate-800">
                                    <span x-text="piece.libelle"></span>
                                    <span class="text-red-600" x-show="piece.obligatoire && !pieceDejaFournie(piece.id)">*</span>
                                </label>
                                
                                <div x-show="pieceDejaFournie(piece.id)" class="mt-2 mb-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Document déjà enregistré
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1.5">Un fichier est déjà associé à cette pièce. Transférez un nouveau fichier ci-dessous <strong>uniquement si vous souhaitez le remplacer</strong>.</p>
                                </div>

                                <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                       :name="`pieces[${piece.id}]`" 
                                       :required="piece.obligatoire && !pieceDejaFournie(piece.id)"
                                       class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-white file:border file:border-slate-300 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-50 transition">
                            </div>
                        </template>
                    </div>
                </div>
            </section>






            {{-- ÉTAPE 2 — Demandeur ------------------------------------------ --}}
            <section x-show="step === 2" x-cloak class="p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">2. Informations du demandeur</h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nom" value="Nom et prénoms" />
                        <x-text-input id="nom" name="nom" type="text" class="mt-1 block w-full"
                                      :value="old('nom', optional($demande->acteur)->nom ?? optional($demande->acteur)->nom_acteur)" required />
                        <x-input-error :messages="$errors->get('nom')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="telephone" value="Téléphone" />
                        <x-text-input id="telephone" name="telephone" type="tel" class="mt-1 block w-full"
                                      :value="old('telephone', optional($demande->acteur)->telephone)" required />
                        <x-input-error :messages="$errors->get('telephone')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="email" value="Adresse e-mail (facultative)" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email', optional($demande->acteur)->email)" />
                    </div>
                </div>
            </section>










            {{-- ÉTAPE 3 — Demande -------------------------------------------- --}}
            <section x-show="step === 3" x-cloak class="p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">3. Informations sur la demande</h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="id_localite" value="Localité" />
                        <select id="id_localite" name="id_localite" required
                                class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                            <option value="">— Sélectionner —</option>
                            @foreach ($localites as $localite)
                                <option value="{{ $localite->id_localite }}" @selected(old('id_localite', $idLocalite) == $localite->id_localite)>
                                    {{ $localite->lib_localite }} ({{ optional($localite->departement)->lib_departement }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_localite')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="id_campagne" value="Campagne" />
                        <select id="id_campagne" name="id_campagne" required
                                class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                            <option value="">— Sélectionner —</option>
                            @foreach ($campagnes as $campagne)
                                <option value="{{ $campagne->id_campagne }}" @selected(old('id_campagne',$idCampagne) == $campagne->id_campagne)>
                                    {{ $campagne->lib_campagne }} : ({{ $campagne->date_debut_campagne->format('m-Y') }} - {{ $campagne->date_fin_campagne->format('m-Y') }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_campagne')" class="mt-1" />
                    </div>


                    <div>
                        <x-input-label for="periode_debut_mise_en_place" value="Début de mise en place" />
                        <x-text-input id="periode_debut_mise_en_place" name="periode_debut_mise_en_place" type="date"
                                      class="mt-1 block w-full"
                                      :value="old('periode_debut_mise_en_place', $debutMiseEnPlace)"
                                      required />
                        <x-input-error :messages="$errors->get('periode_debut_mise_en_place')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="periode_fin_mise_en_place" value="Fin de mise en place" />
                        <x-text-input id="periode_fin_mise_en_place" name="periode_fin_mise_en_place" type="date"
                                      class="mt-1 block w-full"
                                      :value="old('periode_fin_mise_en_place', $finMiseEnPlace)"
                                      required />
                        <x-input-error :messages="$errors->get('periode_fin_mise_en_place')" class="mt-1" />
                    </div>
                </div>

                <fieldset class="rounded-lg border border-slate-200 p-4">
                    <legend class="px-2 text-sm font-medium text-slate-700">Superficie par système de production (ha)</legend>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($typesSystemesProductions as $type)
                            <div>
                                <x-input-label :for="'superficie-'.$type->id_type_systeme_production"
                                               :value="$type->lib_type_systeme_production" />
                                <x-text-input :id="'superficie-'.$type->id_type_systeme_production"
                                              :name="'superficies['.$type->id_type_systeme_production.']'"
                                              type="number" step="0.01" min="0" placeholder="0"
                                              class="mt-1 block w-full"
                                              :value="old('superficies.'.$type->id_type_systeme_production, $superficiesDB[$type->id_type_systeme_production] ?? '')" />
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            </section>








            {{-- ÉTAPE 4 — Appuis & pièces ------------------------------------ --}}
            <section x-show="step === 4" x-cloak class="p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">4. Types d'appuis sollicités</h2>
                </div>

                <div class="space-y-3">
                    <template x-for="(ligne, index) in lignes" :key="index">
                        <div class="grid gap-3 sm:grid-cols-12 items-end rounded-lg border border-slate-200 p-3">
                            <div class="sm:col-span-7">
                                <label class="block text-sm text-slate-700" x-text="'Appui n°' + (index + 1)"></label>
                                
                                {{-- Select du type d'appui et affichage des anciennes lignes --}}
                                <select :name="`lignes[${index}][id_type_appui]`" x-model="ligne.id_type_appui" required
                                        class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                                    <option value="">Sélectionner un type d'appui</option>

                                    {{-- Affichage des types d'appuis par service --}}
                                    @foreach ($typesAppuis->groupBy(fn ($typeAppui) => $typeAppui?->service->lib_service ?? 'Autres') as $serviceTypeAppui => $groupe)
                                        <optgroup label="{{ $serviceTypeAppui }}">
                                            @foreach ($groupe as $typeAppui)
                                                <option value="{{ $typeAppui->id_type_appui }}">{{ $typeAppui->lib_type_appui }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm text-slate-700">Unité</label>
                                <input type="text" 
                                    :value="infosAppui(ligne.id_appui)?.unite || ''"
                                    readonly tabindex="-1" placeholder="—"
                                    class="mt-1 block w-full border-gray-300 bg-slate-100 text-slate-600 rounded-md shadow-sm cursor-not-allowed">
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-sm text-slate-700">Quantité</label>
                                <input type="number" min="1" step="1" required
                                       :name="`lignes[${index}][quantite_demandee]`"
                                       x-model="ligne.quantite_demandee"
                                       class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                            </div>

                            <div class="sm:col-span-2">
                                <button type="button" @click="retirerLigne(index)"
                                        :disabled="lignes.length === 1"
                                        class="w-full rounded-md border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Retirer
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="ajouterLigne()"
                        class="inline-flex items-center gap-1.5 rounded-md border border-[--color-primary] px-3 py-2 text-sm font-medium text-[--color-primary] hover:bg-emerald-50">
                    <span class="material-symbols-outlined text-base">add</span> Ajouter un appui
                </button>
            </section>










            
            {{-- Navigation --------------------------------------------------- --}}
            <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4 bg-slate-50 rounded-b-xl">
                <button type="button" @click="precedent()" x-show="step > 1" x-cloak
                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Précédent
                </button>
                <span x-show="step === 1"></span>

                <button type="button" @click="suivant()" x-show="step < totalSteps"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Suivant
                </button>

                <div x-show="step === totalSteps" x-cloak class="flex gap-3">
                    <a href="{{ route('demandes.index') }}" class="rounded-md border border-slate-300 bg-white px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Annuler
                    </a>
                    <button type="submit" class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow">
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection