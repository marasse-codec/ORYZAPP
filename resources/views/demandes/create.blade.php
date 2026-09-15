@extends($layout ?? 'layouts.app')

@php
    // Pièces attendues par type d'acteur : le choix de l'étape 1 pilote la
    // liste des téléversements de l'étape 4.
    $piecesParTypeActeur = $typesActeurs->mapWithKeys(fn ($type) => [
        $type->id_type_acteur => $type->typesPieces->map(fn ($piece) => [
            'id' => $piece->id_type_piece,
            'libelle' => $piece->lib_type_piece,
            'obligatoire' => (bool) $piece->pivot->est_obligatoire,
        ])->values(),
    ]);

    // Le demandeur ne connaît que l'unité de mesure : l'état des stocks ne
    // regarde que la commission, qui arbitre, et le magasin, qui sert.
    $appuisIndex = $appuis->mapWithKeys(fn ($appui) => [
        $appui->id_appui => [
            'unite' => $appui->uniteAppui?->lib_unite_appui ?? 'unité',
        ],
    ]);

    $lignesInitiales = old('lignes', [['id_appui' => '', 'unite' => '' , 'quantite_demandee' => '']]);
@endphp

@section('content')
<div
    x-data="{
        step: 1,
        totalSteps: 4,
        idTypeActeur: '{{ old('id_type_acteur', '') }}',
        lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
        piecesParTypeActeur: {{ Illuminate\Support\Js::from($piecesParTypeActeur) }},
        appuisIndex: {{ Illuminate\Support\Js::from($appuisIndex) }},

        get piecesAttendues() {
            return this.piecesParTypeActeur[this.idTypeActeur] ?? [];
        },

        infosAppui(id) {
            return this.appuisIndex[id] ?? null;
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
                    <h1 class="text-2xl font-bold text-[--color-primary]">Nouvelle demande d'appui</h1>
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
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">La demande n'a pas pu être enregistrée :</p>
                <ul class="mt-1.5 list-disc list-inside text-sm text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('demandes.store') }}" enctype="multipart/form-data"
              class="bg-white rounded-xl shadow-sm border border-slate-200">
            @csrf

            {{-- ÉTAPE 1 — Profil d'acteur ------------------------------------ --}}
            <section x-show="step === 1" x-cloak class="p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">1. Profil du demandeur</h2>
                    <p class="text-sm text-slate-500">Ce choix détermine les pièces justificatives à fournir.</p>
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
                                    {{ $typeActeur->typesPieces->count() }} pièce(s) à fournir
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('id_type_acteur')" class="mt-1" />
                
                <div>
                    <h3 class="text-base font-semibold text-[--color-primary]">Pièces justificatives</h3>
                    <p class="text-sm text-slate-500" x-show="!idTypeActeur" x-cloak>
                        Sélectionnez d'abord un profil à l'étape 1.
                    </p>
                    <p class="text-sm text-slate-500" x-show="idTypeActeur && piecesAttendues.length === 0" x-cloak>
                        Aucune pièce n'est exigée pour ce profil.
                    </p>

                    <div class="mt-3 space-y-3">
                        <template x-for="piece in piecesAttendues" :key="piece.id">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">
                                    <span x-text="piece.libelle"></span>
                                    <span class="text-red-600" x-show="piece.obligatoire">*</span>
                                </label>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                       :name="`pieces[${piece.id}]`" :required="piece.obligatoire"
                                       class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-[--color-primary] file:px-3 file:py-2 file:text-sm file:text-white hover:file:opacity-90">
                            </div>
                        </template>
                    </div>
                    <x-input-error :messages="$errors->get('pieces')" class="mt-1" />
                </div>
            </section>

            {{-- ÉTAPE 2 — Demandeur ------------------------------------------ --}}
            <section x-show="step === 2" x-cloak class="p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">2. Informations du demandeur</h2>
                    <p class="text-sm text-slate-500">Le téléphone identifie le demandeur d'une demande à l'autre.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nom" value="Nom et prénoms" />
                        <x-text-input id="nom" name="nom" type="text" class="mt-1 block w-full"
                                      :value="old('nom')" required />
                        <x-input-error :messages="$errors->get('nom')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="telephone" value="Téléphone" />
                        <x-text-input id="telephone" name="telephone" type="tel" class="mt-1 block w-full"
                                      :value="old('telephone')" required />
                        <x-input-error :messages="$errors->get('telephone')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="email" value="Adresse e-mail (facultative)" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                </div>
            </section>

            {{-- ÉTAPE 3 — Demande -------------------------------------------- --}}
            <section x-show="step === 3" x-cloak class="p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">3. Informations sur la demande</h2>
                    <p class="text-sm text-slate-500">Localité, superficies exploitées et date de mise en place.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="id_localite" value="Localité" />
                        <select id="id_localite" name="id_localite" required
                                class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                            <option value="">— Sélectionner —</option>
                            @foreach ($localites as $localite)
                                <option value="{{ $localite->id_localite }}" @selected(old('id_localite') == $localite->id_localite)>
                                    {{ $localite->lib_localite }} ({{ $localite->departement?->lib_departement }})
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
                                <option value="{{ $campagne->id_campagne }}" @selected(old('id_campagne') == $campagne->id_campagne)>
                                    {{ $campagne->lib_campagne }} : ({{ $campagne->date_debut_campagne->format('m-Y') }} - {{ $campagne->date_fin_campagne->format('m-Y') }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_campagne')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="debut_periode_mise_en_place" value="Début de mise en place" />
                        <x-text-input id="debut_periode_mise_en_place" name="debut_periode_mise_en_place" type="date"
                                      class="mt-1 block w-full"
                                      :value="old('debut_periode_mise_en_place')"
                                      min="{{ now()->toDateString() }}" required />
                        <x-input-error :messages="$errors->get('debut_periode_mise_en_place')" class="mt-1" />
                    </div>
                    
                </div>

                <fieldset class="rounded-lg border border-slate-200 p-4">
                    <legend class="px-2 text-sm font-medium text-slate-700">Superficie par système de production (ha)</legend>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($typesSystemesProduction as $type)
                            <div>
                                <x-input-label :for="'superficie-'.$type->id_type_systeme_production"
                                               :value="$type->lib_type_systeme_production" />
                                <x-text-input :id="'superficie-'.$type->id_type_systeme_production"
                                              :name="'superficies['.$type->id_type_systeme_production.']'"
                                              type="number" step="0.01" min="0" placeholder="0"
                                              class="mt-1 block w-full"
                                              :value="old('superficies.'.$type->id_type_systeme_production)" />
                            </div>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('superficies')" class="mt-2" />
                </fieldset>
            </section>

            {{-- ÉTAPE 4 — Appuis & pièces ------------------------------------ --}}
            <section x-show="step === 4" x-cloak class="p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">4. Appuis sollicités</h2>
                    <p class="text-sm text-slate-500">Choisissez chaque appui et la quantité souhaitée.</p>
                </div>

                <div class="space-y-3">
                    <template x-for="(ligne, index) in lignes" :key="index">
                        <div class="grid gap-3 sm:grid-cols-12 items-end rounded-lg border border-slate-200 p-3">
                            <div class="sm:col-span-7">
                                <label class="block text-sm text-slate-700" x-text="'Appui n°' + (index + 1)"></label>
                                <select :name="`lignes[${index}][id_appui]`" x-model="ligne.id_appui" required
                                        class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                                    <option value="">— Sélectionner un appui —</option>
                                    @foreach ($appuis->groupBy(fn ($appui) => $appui->typeAppui?->libelle ?? 'Autres') as $libelleType => $groupe)
                                        <optgroup label="{{ $libelleType }}">
                                            @foreach ($groupe as $appui)
                                                <option value="{{ $appui->id_appui }}">{{ $appui->libelle }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm text-slate-700">Unité</label>
                            <input type="text" 
                                :value="infosAppui(ligne.id_appui)?.unite || ''"
                                readonly
                                tabindex="-1"
                                placeholder="—"
                                class="mt-1 block w-full border-gray-300 bg-slate-100 text-slate-600 focus:border-gray-300 focus:ring-0 rounded-md shadow-sm cursor-not-allowed">
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
                <x-input-error :messages="$errors->get('lignes')" class="mt-1" />

                <hr class="border-slate-200">

                
            </section>

            {{-- Navigation --------------------------------------------------- --}}
            <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4">
                <button type="button" @click="precedent()" x-show="step > 1" x-cloak
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Précédent
                </button>
                <span x-show="step === 1"></span>

                <button type="button" @click="suivant()" x-show="step < totalSteps"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Suivant
                </button>

                <button type="submit" x-show="step === totalSteps" x-cloak
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Soumettre la demande
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
