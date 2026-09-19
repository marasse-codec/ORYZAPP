@extends($layout ?? 'layouts.aderiz')

@php
    $lignesInitiales = old('lignes', [
        ['id_appui' => '', 'quantite_attribuee' => 1, 'cout_attribution' => 0, 'remise' => 0]
    ]);

    $catalogueIndex =$catalogue->mapWithKeys(fn ($appui) => [$appui->id_appui => [
            'libelle' => $appui->libelle,
            'cout_unitaire' => $appui->cout_unitaire,
            'stock' => $appui->quantite_en_stock,
        ],
    ]);
@endphp

@section('content')
<div class="min-h-screen bg-[--color-secondary] px-6 py-8">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- En-tête -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-[--color-primary]">
                Nouvelle Attribution Directe / Don
            </h1>
            <p class="text-sm text-slate-500">
                Saisie d'une allocation directe d'appuis sans passer par le circuit de demande standard.
            </p>
        </div>

        @include('partials.flash')

        <!-- Synthèse rétractable des stocks -->
        <div x-data="{ ouvert: false }" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <button type="button" @click="ouvert = !ouvert" 
                    class="w-full px-6 py-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[--color-primary]">inventory_2</span>
                    <span class="font-semibold text-slate-800">Synthèse des stocks du service</span>
                    <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-medium">
                        {{ count($synthese) }} appuis
                    </span>
                </div>
                <div class="flex items-center gap-1 text-sm text-[--color-primary] font-medium">
                    <span x-text="ouvert ? 'Masquer la synthèse' : 'Afficher la synthèse'"></span>
                    <span class="material-symbols-outlined transition-transform duration-200" :class="{ 'rotate-180': ouvert }">expand_more</span>
                </div>
            </button>

            <div x-show="ouvert" x-collapse class="p-6 border-t border-slate-200">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($synthese as $ligne)
                        @php $disponible =$ligne['disponible']; @endphp
                        <div class="rounded-lg border border-slate-200 p-4 bg-white">
                            <p class="text-sm font-semibold text-slate-800">{{ $ligne['libelle'] }}</p>
                            <dl class="mt-2 space-y-1 text-xs text-slate-600">
                                <div class="flex justify-between">
                                    <dt>Stock total</dt>
                                    <dd class="font-medium">{{ $ligne['stock'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Déjà engagé</dt>
                                    <dd class="font-medium">{{ $ligne['engagee'] }}</dd>
                                </div>
                                <div class="flex justify-between border-t border-slate-200 pt-1">
                                    <dt class="font-semibold">Disponible</dt>
                                    <dd class="font-bold {{ $disponible <= 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                        {{ $disponible }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 col-span-full">Aucun appui répertorié pour ce service.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Formulaire principal -->
        <form action="{{ route('attributions.storeDirect') }}" method="POST"
              x-data="{
                    modeActeur: '{{ old('mode_acteur', 'existant') }}',
                    lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
                    catalogue: {{ Illuminate\Support\Js::from($catalogueIndex) }},

                    estSelectionne(idAppui, currentIndex) {
                        if (!idAppui) return false;
                        return this.lignes.some((ligne, idx) => idx !== currentIndex && String(ligne.id_appui) === String(idAppui));
                    },

                    onAppuiChange(index) {
                        const id = this.lignes[index].id_appui;
                        if (id && this.catalogue[id]) {
                            this.lignes[index].cout_attribution = this.catalogue[id].cout_unitaire ?? 0;
                        }
                    },

                    montant(ligne) {
                        const q = Number(ligne.quantite_attribuee) || 0;
                        const c = Number(ligne.cout_attribution) || 0;
                        const r = Number(ligne.remise) || 0;
                        return q * c * (1 - r / 100);
                    },

                    get total() {
                        return this.lignes.reduce((somme, l) => somme + this.montant(l), 0);
                    },

                    ajouter() { 
                        this.lignes.push({ id_appui: '', quantite_attribuee: 1, cout_attribution: 0, remise: 0 }); 
                    },
                    retirer(i) { 
                        if (this.lignes.length > 1) this.lignes.splice(i, 1); 
                    },
                }"
              class="space-y-6">
            @csrf

            <!-- Informations sur le bénéficiaire et le contexte -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-base font-semibold text-[--color-primary]">Cadre de l'attribution</h2>
                    
                    <!-- Toggle Sélection / Création d'acteur -->
                    <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs">
                        <button type="button" 
                                @click="modeActeur = 'existant'"
                                :class="{ 'bg-white text-[--color-primary] shadow-sm font-semibold': modeActeur === 'existant', 'text-slate-600': modeActeur !== 'existant' }"
                                class="rounded-md px-3 py-1.5 transition-all">
                            Acteur existant
                        </button>
                        <button type="button" 
                                @click="modeActeur = 'nouveau'"
                                :class="{ 'bg-white text-[--color-primary] shadow-sm font-semibold': modeActeur === 'nouveau', 'text-slate-600': modeActeur !== 'nouveau' }"
                                class="rounded-md px-3 py-1.5 transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">person_add</span>
                            Nouveau bénéficiaire
                        </button>
                    </div>
                </div>

                <input type="hidden" name="mode_acteur" :value="modeActeur">

                <!-- Mode 1: Acteur Existant -->
                <div x-show="modeActeur === 'existant'" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="id_acteur" value="Acteur bénéficiaire" />
                        <select id="id_acteur" name="id_acteur" :required="modeActeur === 'existant'"
                                class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            <option value="">— Choisir l'acteur —</option>
                            @foreach ($acteurs as $acteur)
                                <option value="{{ $acteur->id_acteur }}" @selected(old('id_acteur') == $acteur->id_acteur)>
                                    {{ $acteur->nom }} {{ $acteur->prenoms ? '('.$acteur->prenoms.')' : '' }} {{ $acteur->telephone ? '- '.$acteur->telephone : '' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_acteur')" class="mt-1" />
                    </div>
                </div>

                <!-- Mode 2: Création Rapide de Bénéficiaire -->
                <div x-show="modeActeur === 'nouveau'" class="grid gap-4 sm:grid-cols-2 bg-slate-50/70 p-4 rounded-lg border border-slate-200">
                    <div>
                        <x-input-label for="nom_acteur" value="Nom / Raison sociale de l'acteur *" />
                        <x-text-input id="nom_acteur" name="nom_acteur" type="text" class="mt-1 block w-full text-sm" 
                                    x-bind:required="modeActeur === 'nouveau'" 
                                    :value="old('nom_acteur')" 
                                    placeholder="Ex. : KOUASSI ou COOP-CA ADERIZ" />
                        <x-input-error :messages="$errors->get('nom_acteur')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="prenoms_acteur" value="Prénoms (optionnel)" />
                        <x-text-input id="prenoms_acteur" name="prenoms_acteur" type="text" class="mt-1 block w-full text-sm" :value="old('prenoms_acteur')" placeholder="Ex. : Jean-Baptiste" />
                        <x-input-error :messages="$errors->get('prenoms_acteur')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="telephone_acteur" value="Téléphone (optionnel)" />
                        <x-text-input id="telephone_acteur" name="telephone_acteur" type="text" class="mt-1 block w-full text-sm" :value="old('telephone_acteur')" placeholder="Ex. : 0701020304" />
                        <x-input-error :messages="$errors->get('telephone_acteur')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email_acteur" value="Adresse Email (optionnel)" />
                        <x-text-input id="email_acteur" name="email_acteur" type="email" class="mt-1 block w-full text-sm" :value="old('email_acteur')" placeholder="Ex. : exemple@domaine.ci" />
                        <x-input-error :messages="$errors->get('email_acteur')" class="mt-1" />
                    </div>
                </div>

                <!-- Champs de contexte communs -->
                <div class="grid gap-4 sm:grid-cols-3 border-t border-slate-100 pt-3">
                    <div>
                        <x-input-label for="id_type_acteur" value="Type d'acteur *" />
                        <select id="id_type_acteur" name="id_type_acteur" required
                                class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            <option value="">— Choisir le type —</option>
                            @foreach ($typesActeurs as $type)
                                <option value="{{ $type->id_type_acteur }}" @selected(old('id_type_acteur') == $type->id_type_acteur)>
                                    {{ $type->lib_type_acteur }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_type_acteur')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="id_localite" value="Localité *" />
                        <select id="id_localite" name="id_localite" required
                                class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            <option value="">— Choisir la localité —</option>
                            @foreach ($localites as $loc)
                                <option value="{{ $loc->id_localite }}" @selected(old('id_localite') == $loc->id_localite)>
                                    {{ $loc->lib_localite }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_localite')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="id_campagne" value="Campagne *" />
                        <select id="id_campagne" name="id_campagne" required
                                class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            <option value="">— Choisir la campagne —</option>
                            @foreach ($campagnes as $c)
                                <option value="{{ $c->id_campagne }}" @selected(old('id_campagne') == $c->id_campagne)>
                                    {{ $c->lib_campagne }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_campagne')" class="mt-1" />
                    </div>
                </div>
            </div>

            <!-- Liste des Appuis -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Choix des appuis</h2>

                <div class="space-y-3">
                    <template x-for="(ligne, index) in lignes" :key="index">
                        <div class="grid gap-3 sm:grid-cols-12 items-end rounded-lg border border-slate-200 p-3">
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-slate-600">Appui</label>
                                <select :name="`lignes[${index}][id_appui]`" 
                                        x-model="ligne.id_appui" 
                                        @change="onAppuiChange(index)"
                                        required
                                        class="mt-1 block w-full text-sm border-gray-300 bg-slate-50 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md font-semibold text-slate-800">
                                    <option value="">— Sélectionner un appui —</option>
                                    @foreach ($catalogue as $appui)
                                        <option value="{{ $appui->id_appui }}" 
                                                :disabled="estSelectionne({{ $appui->id_appui }}, index)">
                                            {{ $appui->libelle }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-600">Quantité</label>
                                <input type="number" min="1" required :name="`lignes[${index}][quantite_attribuee]`"
                                       x-model="ligne.quantite_attribuee"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-600">Coût unitaire</label>
                                <input type="number" min="0" step="0.01" :name="`lignes[${index}][cout_attribution]`"
                                       x-model="ligne.cout_attribution"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-600">Remise %</label>
                                <input type="number" min="0" max="100" step="0.01" :name="`lignes[${index}][remise]`"
                                       x-model="ligne.remise"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-600">Montant</label>
                                <input type="text" readonly
                                    class="mt-1 block w-full text-sm border-gray-300 bg-slate-50 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md font-semibold text-slate-800"
                                    :value="montant(ligne).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' F'">
                            </div>

                            <div class="sm:col-span-1">
                                <button type="button" @click="retirer(index)" :disabled="lignes.length === 1"
                                        class="w-full rounded-md border border-red-200 px-2 py-2 text-xs text-red-700 hover:bg-red-50 disabled:opacity-40">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <button type="button" @click="ajouter()"
                            class="inline-flex items-center gap-1.5 rounded-md border border-[--color-primary] px-3 py-2 text-sm font-medium text-[--color-primary] hover:bg-emerald-50">
                        <span class="material-symbols-outlined text-base">add</span> Ajouter une ligne
                    </button>
                    <p class="text-sm text-slate-600">
                        Total :
                        <span class="text-base font-bold text-[--color-primary]"
                              x-text="total.toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' F'"></span>
                    </p>
                </div>
            </div>

            <!-- Note / Justification obligatoire -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-input-label for="commentaires" value="Note de justification (Obligatoire)" />
                <textarea id="commentaires" name="commentaires" rows="3" required
                          class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm"
                          placeholder="Ex. : Attribution directe dans le cadre d'un don accordé suite aux intempéries.">{{ old('commentaires') }}</textarea>
                <x-input-error :messages="$errors->get('commentaires')" class="mt-1" />
            </div>

            <!-- Boutons de soumission -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('attributions.index') }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Enregistrer l'attribution
                </button>
            </div>
        </form>

    </div>
</div>
@endsection