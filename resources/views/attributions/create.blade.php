@extends($layout ?? 'layouts.aderiz')

@php
    // Le formulaire est pré-rempli avec ce qui a été demandé : la commission
    // ajuste les quantités et les coûts plutôt que de tout ressaisir.
    $lignesInitiales = old('lignes', $demande->lignes->map(fn ($ligne) => [
        'id_appui' => $ligne->id_appui,
        'quantite_attribuee' => $ligne->quantite_demandee,
        'cout_attribution' => $ligne->appui?->cout_unitaire,
        'remise' => 0,
    ])->all());

    $catalogueIndex = $catalogue->mapWithKeys(fn ($appui) => [
        $appui->id_appui => [
            'libelle' => $appui->libelle,
            'cout_unitaire' => $appui->cout_unitaire,
            'stock' => $appui->quantite_en_stock,
            'unite' => $appui->typeAppui?->uniteTypeAppui?->lib_unite_type_appui ?? 'unité',
        ],
    ]);
@endphp

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-5xl mx-auto space-y-6">

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-[--color-primary]">
                DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                — {{ $demande->acteur?->nom }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $demande->typeActeur?->lib_type_acteur }} · {{ $demande->localite?->lib_localite }} ·
                {{ number_format($demande->systemesProductions->sum('superficie'), 2, ',', ' ') }} ha
            </p>
        </div>

        @include('partials.flash')

        
        {{-- Synthèse : demandé / en stock / déjà engagé, appui par appui --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Synthèse des quantités pour ce dossier</h2>
            @foreach ($demande->lignes as $ligne)
                    <div class="flex justify-between">
                        <dt class="font-semibold">{{$ligne->typeAppui->lib_type_appui}} demandés : {{ $ligne->quantite_demandee }}</dt>
                    </div>
                @endforeach
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($synthese as $ligne)
                    @php $disponible = $ligne['stock'] - $ligne['engagee']; @endphp
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ $ligne['libelle'] }}</p>
                        <dl class="mt-2 space-y-1 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <dt>En stock</dt>
                                <dd class="font-medium">{{ $ligne['stock'] }} {{ $ligne['unite'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Déjà attribué</dt>
                                <dd class="font-medium">{{ $ligne['engagee'] }} {{ $ligne['unite'] }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-1">
                                <dt class="font-semibold">Disponible</dt>
                                <dd class="font-bold {{ $disponible < $ligne['demandee'] ? 'text-red-700' : 'text-emerald-700' }}">
                                    {{ $disponible }} {{ $ligne['unite'] }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>





            <form action="{{ route('attributions.store', $demande->id_demande) }}" method="POST"
                  x-data="{
                        // Reçoit le pré-remplissage du contrôleur (lignes demandées à l'origine)
                        lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
                        
                        // Index JSON du catalogue [id_appui => { libelle, stock, unite, cout_unitaire }]
                        catalogue: {{ Illuminate\Support\Js::from($catalogueIndex) }},

                        // Retourne les infos d'un appui à partir de son ID
                        infos(id) { return this.catalogue[id] ?? null; },

                        // Empêche la sélection d'un même appui dans deux lignes différentes
                        estSelectionne(idAppui, currentIndex) {
                            if (!idAppui) return false;
                            return this.lignes.some((ligne, idx) => idx !== currentIndex && String(ligne.id_appui) === String(idAppui));
                        },

                        // Remplit automatiquement le coût unitaire dès qu'on choisit un appui
                        onAppuiChange(index) {
                            const id = this.lignes[index].id_appui;
                            if (id && this.catalogue[id]) {
                                this.lignes[index].cout_attribution = this.catalogue[id].cout_unitaire ?? 0;
                            }
                        },

                        // Calcule le sous-total de la ligne (Quantité * Coût * (1 - Remise %))
                        montant(ligne) {
                            const q = Number(ligne.quantite_attribuee) || 0;
                            const c = Number(ligne.cout_attribution) || 0;
                            const r = Number(ligne.remise) || 0;
                            return q * c * (1 - r / 100);
                        },

                        // Calcul du montant global de la commande
                        get total() {
                            return this.lignes.reduce((somme, l) => somme + this.montant(l), 0);
                        },

                        // Actions d'ajout et suppression de lignes
                        ajouter() { 
                            this.lignes.push({ id_appui: '', quantite_attribuee: 1, cout_attribution: 0, remise: 0 }); 
                        },
                        retirer(i) { 
                            if (this.lignes.length > 1) this.lignes.splice(i, 1); 
                        },
                    }"
                  class="space-y-6">
                @csrf

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="space-y-3">
                        <template x-for="(ligne, index) in lignes" :key="index">
                            <div class="grid gap-3 sm:grid-cols-12 items-end rounded-lg border border-slate-200 p-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-slate-600">Appui</label>
                                    <select :name="`lignes[${index}][id_appui]`" 
                                            x-model="ligne.id_appui" 
                                            @change="onAppuiChange(index)"
                                            required
                                            class="mt-1 block w-full text-sm border-gray-300 bg-slate-50 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md font-semibold text-slate-800"">
                                        <option value="">— Sélectionner un appui —</option>
                                        @foreach ($catalogue as $appui)
                                            <!-- Le bouton est désactivé si l'appui est déjà choisi ailleurs -->
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

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <x-input-label for="commentaires" value="Observations &amp; motivations du procès-verbal d'arbitrage" />
                    <textarea id="commentaires" name="commentaires" rows="4"
                              class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm"
                              placeholder="Ex. : la quantité a été estimée après visite de la parcelle.">{{ old('commentaires') }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('demandes.show', $demande->id_demande) }}"
                       class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                    <button type="submit"
                            class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                        Valider l'attribution
                    </button>
                </div>
            </form>

            {{-- Rejet motivé : l'autre issue possible de l'arbitrage --}}
            <div x-data="{ ouvert: false }" class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-red-800">Rejeter le dossier</h2>
                        <p class="text-sm text-slate-500">Le motif saisi est ce que le demandeur recevra en retour.</p>
                    </div>
                    <button type="button" @click="ouvert = !ouvert"
                            class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                        <span x-text="ouvert ? 'Annuler le rejet' : 'Rejeter'"></span>
                    </button>
                </div>

                <form x-show="ouvert" x-cloak method="POST"
                      action="{{ route('demandes.rejeter') }}"
                      class="mt-4 space-y-4"
                      onsubmit="return confirm('Rejeter définitivement ce dossier ?')">
                    @csrf
                        @method('PATCH')
                    <div>
                        {{--id demande--}}
                        <input hidden name="id_demande" value="{{$demande->id_demande}}">
                        <x-input-label for="motifs_rejet" value="Motifs du rejet" />
                        <textarea id="motifs_rejet" name="motifs_rejet" rows="3" required
                                  class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                                  placeholder="Ex. : superficie déclarée hors barème pour ce type d'acteur.">{{ old('motifs_rejet') }}</textarea>
                        <x-input-error :messages="$errors->get('motifs_rejet')" class="mt-1" />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="rounded-md bg-red-700 px-5 py-2 text-sm font-semibold text-white hover:bg-red-800">
                            Confirmer le rejet
                        </button>
                    </div>
                </form>
            </div>
    </div>
</div>
@endsection
