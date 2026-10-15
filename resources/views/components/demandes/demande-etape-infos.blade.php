@props(['localites', 'campagnes', 'typesSystemesProductions', 'demande' => null])

<section data-etape="3" x-show="step === 3" x-cloak class="p-6 space-y-5">
    <div>
        <h2 class="text-lg font-semibold text-[--color-primary]">3. Informations sur la demande</h2>
        <p class="text-sm text-slate-500">Localités concernées, superficies exploitées et dates de mise en place.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2 space-y-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <template x-for="idLoc in selectedLocalites" :key="'hidden-loc-' + idLoc">
                <input type="hidden" name="localites[]" :value="idLoc">
            </template>

            <div>
                <x-input-label value="Localité(s) concernée(s)" />
                <p class="text-xs text-slate-500">
                    Sélectionnez un département, puis cochez la ou les localités concernées.
                </p>
            </div>

            <div>
                <label for="select_departement" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    1. Sélectionner un département
                </label>
                <select id="select_departement" x-model="selectedDepartement"
                        class="block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm text-sm bg-white">
                    <option value="">Choisir un département</option>
                    @foreach ($localites->pluck('departement')->filter()->unique('id_departement')->sortBy('lib_departement') as $departement)
                        <option value="{{ $departement->id_departement }}">
                            {{ $departement->lib_departement }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div x-show="selectedDepartement" x-cloak class="space-y-2 border-t border-slate-200 pt-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    2. Cocher les localités<span class="ml-0.5 text-red-600" aria-hidden="true">*</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-52 overflow-y-auto p-1">
                    <template x-for="loc in localitesList.filter(l => l.id_departement == selectedDepartement)" :key="loc.id_localite">
                        <label class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-emerald-50/50 cursor-pointer transition-colors text-sm">
                            <input type="checkbox"
                                   :value="loc.id_localite"
                                   x-model.number="selectedLocalites"
                                   class="rounded text-[--color-primary] focus:ring-[--color-primary] border-slate-300">
                            <span x-text="loc.lib_localite" class="text-slate-700 font-medium"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div x-show="selectedLocalites.length > 0" class="border-t border-slate-200 pt-3 space-y-2" x-cloak>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Localités sélectionnées (<span x-text="selectedLocalites.length"></span>)
                    </span>
                    <button type="button" @click="selectedLocalites = []" class="text-xs text-red-600 hover:underline">
                        Tout décocher
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <template x-for="idLoc in selectedLocalites" :key="'badge-loc-' + idLoc">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span x-text="localitesList.find(l => l.id_localite == idLoc)?.lib_localite || 'ID: ' + idLoc"></span>
                            <button type="button" @click="selectedLocalites = selectedLocalites.filter(i => i != idLoc)" 
                                    class="hover:text-red-700 font-bold ml-1 text-sm leading-none">&times;</button>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Les localités sont des cases à cocher : ce champ invisible porte la règle « au moins une ». --}}
            <input type="text" class="sr-only" tabindex="-1" aria-hidden="true" required
                   :value="selectedLocalites.length ? 'ok' : ''"
                   x-effect="$el.setCustomValidity(selectedLocalites.length ? '' : 'Sélectionnez au moins une localité.')">
            <x-input-error :messages="$errors->get('localites')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="id_campagne" value="Campagne" obligatoire />
            <select id="id_campagne" name="id_campagne" required
                    class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                <option value="">Sélectionner</option>
                @foreach ($campagnes as $campagne)
                    <option value="{{ $campagne->id_campagne }}" 
                            @selected((string) old('id_campagne', $demande?->id_campagne) === (string) $campagne->id_campagne)>
                        {{ $campagne->lib_campagne }} ({{ $campagne->mois_debut_campagne?->format('m/Y') }} - {{ $campagne->mois_fin_campagne?->format('m/Y') }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('id_campagne')" class="mt-1" />
        </div>

        {{-- La mise en place dure au moins 3 mois : `finMin` = début + 3 mois (cf. ReglesDemande). --}}
        @php
            $debutInitial = old('periode_debut_mise_en_place', $demande?->periode_debut_mise_en_place ? \Carbon\Carbon::parse($demande->periode_debut_mise_en_place)->format('Y-m-d') : '');
            $finInitiale = old('periode_fin_mise_en_place', $demande?->periode_fin_mise_en_place ? \Carbon\Carbon::parse($demande->periode_fin_mise_en_place)->format('Y-m-d') : '');
        @endphp
        <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2"
             x-data="{
                debut: {{ \Illuminate\Support\Js::from($debutInitial) }},
                fin: {{ \Illuminate\Support\Js::from($finInitiale) }},
                get finMin() {
                if (!this.debut) return null;
                const d = new Date(this.debut + 'T00:00:00');
                d.setMonth(d.getMonth() + {{ \App\Domain\Demande\Support\ReglesDemande::DUREE_MINIMALE_MOIS }});
                return d.toLocaleDateString('sv-SE');
            },
             }"
             x-effect="if (fin && finMin && fin < finMin) fin = ''">
            <div>
                <x-input-label for="periode_debut_mise_en_place" value="Période début de mise en place" obligatoire />
                <x-text-input id="periode_debut_mise_en_place" name="periode_debut_mise_en_place" type="date"
                              class="mt-1 block w-full"
                              min="{{ now()->format('Y-m-d') }}"
                              x-model="debut"
                              required />
                <x-input-error :messages="$errors->get('periode_debut_mise_en_place')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="periode_fin_mise_en_place" value="Période fin de mise en place (au moins 3 mois après le début)" obligatoire />
                <x-text-input id="periode_fin_mise_en_place" name="periode_fin_mise_en_place" type="date"
                              class="mt-1 block w-full"
                              x-model="fin" ::min="finMin"
                              required />
                <x-input-error :messages="$errors->get('periode_fin_mise_en_place')" class="mt-1" />
            </div>
        </div>
    </div>

    <fieldset class="rounded-lg border border-slate-200 p-4">
        <legend class="px-2 text-sm font-medium text-slate-700">Superficie par système de production (ha)</legend>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($typesSystemesProductions as $type)
                @php $id = $type->id_type_systeme_production; @endphp
                <div>
                    <x-input-label :for="'superficie-'.$id" :value="$type->lib_type_systeme_production" />
                    <x-text-input :id="'superficie-'.$id"
                                  :name="'superficies['.$id.']'"
                                  type="number" step="0.01" min="0" placeholder="0"
                                  x-model="superficies['{{ $id }}']"
                                  class="mt-1 block w-full transition-colors" />
                </div>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('superficies')" class="mt-2" />
    </fieldset>
</section>
