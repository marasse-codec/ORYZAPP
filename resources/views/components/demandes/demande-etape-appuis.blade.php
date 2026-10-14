@props(['typesAppuis', 'appuisDisponibles', 'demande' => null])

<section x-show="step === 4" x-cloak class="p-6 space-y-6"
         x-data="{
             tousLesAppuis: {{ Illuminate\Support\Js::from($appuisDisponibles) }},
             
             appuisPourType(idType) {
                 if (!idType) return [];
                 return this.tousLesAppuis.filter(a => a.id_type_appui == idType);
             },

             getUnite(idAppui) {
                    if (!idAppui) return '';
                    let appui = this.tousLesAppuis.find(a => a.id == idAppui);
                    return appui ? appui.unite : '';
                }
         }">
    <div>
        <h2 class="text-lg font-semibold text-[--color-primary]">4. Types d'appuis sollicités</h2>
        <p class="text-sm text-slate-500">Choisissez les types d'appuis souhaités, l'appui spécifique et la quantité (facultatifs).</p>
    </div>

    <div class="space-y-4">
        <template x-for="(ligne, index) in lignes" :key="index">
            <div class="grid gap-3 sm:grid-cols-12 items-end rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                
                {{-- 1. Type d'appui --}}
                <div class="sm:col-span-5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1" x-text="'Appui n°' + (index + 1) + ' *'"></label>
                    <select x-bind:name="`lignes[${index}][id_type_appui]`" 
                            x-model="ligne.id_type_appui" 
                            @change="ligne.id_appui = ''" 
                            required
                            class="block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm text-sm bg-white">
                        <option value="">Sélectionner un type d'appui</option>

                        @foreach ($typesAppuis->groupBy(fn ($typeAppui) => $typeAppui?->service->lib_service ?? 'Autres') as $serviceTypeAppui => $groupe)
                            <optgroup label="{{ $serviceTypeAppui }}">
                                @foreach ($groupe as $typeAppui)
                                    <option value="{{ $typeAppui->id_type_appui }}">{{ $typeAppui->lib_type_appui }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Appui spécifique --}}
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Appui spécifique <span class="text-slate-400 font-normal">(Optionnel)</span>
                    </label>
                    <select x-bind:name="`lignes[${index}][id_appui]`" 
                            x-model="ligne.id_appui"
                            :disabled="!ligne.id_type_appui"
                            class="block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm text-sm bg-white disabled:bg-slate-100 disabled:cursor-not-allowed">
                        <option value="">Aucun / Tous</option>
                        <template x-for="appui in appuisPourType(ligne.id_type_appui)" :key="appui.id">
                            <option :value="appui.id" x-text="appui.libelle" :selected="ligne.id_appui == appui.id">></option>
                        </template>
                    </select>
                </div>

                {{-- 3. Quantité --}}
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Quantité <span class="text-slate-400 font-normal">(Opt.)</span>
                    </label>
                    <div class="flex items-center">
                        <x-text-input type="number" step="0.0001" min="0" placeholder="Ex: 5"
                                    x-bind:name="`lignes[${index}][quantite_demandee]`"
                                    x-model="ligne.quantite"
                                    class="block w-full text-sm bg-white rounded-r-none border-r-0" />
                        
                        <span class="inline-flex items-center px-2.5 py-2 rounded-r-md border border-gray-300 bg-slate-100 text-slate-600 text-xs font-medium whitespace-nowrap h-10 select-none"
                            x-text="getUnite(ligne.id_appui) || '-'">
                        </span>
                    </div>
                </div>

                {{-- 4. Cession --}}
                <div x-show="ligne.estEquipement" class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Cession <span class="text-slate-400 font-normal">(Opt.)</span>
                    </label>
                    <div class="flex items-center">
                        <x-text-input type="number" step="0.0001" min="0" placeholder="Ex: 5"
                                    x-bind:name="`lignes[${index}][quantite_demandee]`"
                                    x-model="ligne.quantite_demandee"
                                    class="block w-full text-sm bg-white rounded-r-none border-r-0" />
                        
                        <span class="inline-flex items-center px-2.5 py-2 rounded-r-md border border-gray-300 bg-slate-100 text-slate-600 text-xs font-medium whitespace-nowrap h-10 select-none"
                            x-text="getUnite(ligne.id_appui) || '-'">
                        </span>
                    </div>
                </div>

                {{-- Bouton Retirer --}}
                <div class="sm:col-span-1">
                    <button type="button" @click="retirerLigne(index)"
                            :disabled="lignes.length === 1"
                            class="w-full rounded-md border border-red-200 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        x
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
</section>