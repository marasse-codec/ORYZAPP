@props(['demande' => null]) 

<section data-etape="1" x-show="step === 1" x-cloak class="p-6 space-y-5" @click.away="suggestionsActeurs = []">
    <div>
        <h2 class="text-lg font-semibold text-[--color-primary]">1. Informations du demandeur</h2>
        <p class="text-sm text-slate-500">Renseignez le nom et la date de naissance pour vérifier si l'acteur existe déjà. Le demandeur doit être majeur (18 ans révolus).</p>
    </div>

    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 space-y-4 relative">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="date_naissance" value="Date de naissance" obligatoire class="font-semibold text-slate-800" />
                {{-- Majeur : 18 ans révolus (cf. ReglesDemande). --}}
                <x-text-input id="date_naissance" name="date_naissance" type="date"
                              max="{{ \App\Domain\Demande\Support\ReglesDemande::dateNaissanceMax()->toDateString() }}"
                              title="{{ \App\Domain\Demande\Support\ReglesDemande::messageMajorite() }}" 
                              x-model="dateNaissance" 
                              @change="rechercherActeurParNomEtDate()"
                              class="block w-full bg-white" required />
                <x-input-error :messages="$errors->get('date_naissance')" class="mt-1" />
            </div>

            <div class="relative">
                <x-input-label for="recherche_nom" value="Nom et prénoms" obligatoire class="font-semibold text-slate-800" />
                <x-text-input id="recherche_nom" name="nom" type="text" 
                              x-model="nom" 
                              @input.debounce.300ms="rechercherActeurParNomEtDate()"
                              placeholder="Ex: Kouassi Jean" 
                              class="block w-full bg-white" required />

                <div x-show="suggestionsActeurs.length > 0" x-cloak
                     class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    <template x-for="acteur in suggestionsActeurs" :key="acteur.id_acteur">
                        <div @click="selectionnerActeur(acteur)"
                             class="p-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-100 last:border-none text-sm">
                            <div class="font-semibold text-slate-800" x-text="acteur.nom"></div>
                            <div class="text-xs text-slate-500">
                                Né(e) le : <span x-text="acteur.date_naissance"></span> 
                                <span x-show="acteur.nom_structure"> | Structure : <span x-text="acteur.nom_structure"></span></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="acteurTrouve" class="text-xs font-medium text-emerald-700 flex items-center justify-between" x-cloak>
            <span>Acteur rattaché chargé avec succès !
                <span x-show="demandePrecedente" class="block font-normal text-slate-500">
                    Dernière demande : <span x-text="demandePrecedente"></span>. Choisissez le profil de cette nouvelle demande à l'étape suivante.
                </span>
            </span>
            <button type="button" @click="reinitialiserActeur()" class="text-red-600 underline">
                Réinitialiser
            </button>
        </div>
        
        <div x-show="!acteurTrouve && nom.length >= 2 && dateNaissance" class="text-xs text-slate-500" x-cloak>
            💡 Si aucun résultat n'apparaît, le profil sera mis à jour ou créé avec ce nom et cette date.
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2" x-show="aBesoinNomStructure" x-cloak x-transition>
            <x-input-label for="nom_structure" value="Nom de la structure / organisation" obligatoire />
            <x-text-input id="nom_structure" name="nom_structure" type="text" class="mt-1 block w-full"
                          x-model="nomStructure" x-bind:required="aBesoinNomStructure" />
            <x-input-error :messages="$errors->get('nom_structure')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="telephone" value="Numéro de téléphone" obligatoire />
            <x-text-input id="telephone" name="telephone" type="tel" class="mt-1 block w-full" x-model="telephone" required />
            <x-input-error :messages="$errors->get('telephone')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" value="Adresse e-mail (facultative)" />
            <x-text-input id="email" maxlength="160" name="email" type="email" class="mt-1 block w-full" x-model="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
    </div>
</section>