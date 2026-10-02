@extends($layout ?? 'layouts.app')

@php
    // Récupération sécurisée de la collection des lignes attribuées
    $collectionLignes = $attribution->lignesAttributions ?? $attribution->lignesAttribution ?? $attribution->lignes ?? collect();

    $lignesInitiales = old('lignes', $collectionLignes->map(fn ($ligne) => [
        'id_appui' => $ligne->id_appui,
        'quantite_attribuee' => $ligne->quantite_attribuee,
        'cout_attribution' => $ligne->cout_attribution,
        'remise' => $ligne->remise ?? 0,
    ])->toArray());

    $commentaireInitial = old('commentaires',$attribution->commentaires);

    $catalogueIndex =$catalogue->mapWithKeys(fn ($appui) => [$appui->id_appui => [
            'libelle' => $appui->libelle,
            'cout_unitaire' => $appui->cout_unitaire,
            'stock' => $appui->quantite_en_stock,
        ],
    ]);

    $localitesStr = $demande->localites->pluck('lib_localite')->join(', ');$nomEntite = $demande->nom_structure ?? $demande->acteur?->nom;
@endphp

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- En-tête du dossier --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">
                        Modification d'attribution : DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                        — {{ $nomEntite }}
                    </h1>
                    <p class="text-sm text-[--color-text-secondary] mt-1">
                        {{ $demande->typeActeur?->lib_type_acteur }} · 
                        Localité(s) : {{ $localitesStr ?: 'Non spécifiée' }} ·
                        {{ number_format($demande->systemesProductions->sum('superficie'), 2, ',', ' ') }} ha
                    </p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 self-start sm:self-auto">
                    Édition
                </span>
            </div>
        </div>

        @include('partials.flash')

        {{-- Synthèse : demandé vs déjà attribué / stocks --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-base font-semibold text-[--color-primary]">Synthèse des quantités pour ce dossier</h2>
            
            <div class="grid gap-4 md:grid-cols-1">
                {{-- 1. Appuis initialement sollicités --}}
                <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                    <p class="text-xs font-semibold text-[--color-primary] uppercase tracking-wider mb-3">
                        Appuis initialement sollicités
                    </p>
                    <ul class="space-y-2 text-sm text-[--color-text-primary]">
                        @forelse ($demande->lignes as $ligne)
                            <li class="flex items-center justify-between border-b border-slate-200/60 pb-1.5 last:border-0 last:pb-0">
                                <span>{{ $ligne->typeAppui?->lib_type_appui }}</span>
                                <span class="font-medium text-slate-700">
                                    {{ $ligne->quantite_demandee ?? $ligne->quantite }}
                                </span>
                            </li>
                        @empty
                            <li class="text-xs text-slate-500 italic">Aucun appui spécifié.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- 3. Cartes de disponibilité du stock --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 pt-2">
                @foreach ($synthese as $ligne)
                    @php $disponible = $ligne['stock'] -$ligne['engagee']; @endphp
                    <div class="rounded-lg border border-slate-200 p-4 bg-white shadow-2xs">
                        <p class="text-sm font-semibold text-[--color-text-primary]">{{ $ligne['libelle'] }}</p>
                        <dl class="mt-2 space-y-1 text-xs text-[--color-text-secondary]">
                            <div class="flex justify-between">
                                <dt>En stock</dt>
                                <dd class="font-medium text-slate-800">{{ $ligne['stock'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Déjà attribué (global)</dt>
                                <dd class="font-medium text-slate-800">{{ $ligne['engagee'] }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-1">
                                <dt class="font-semibold">Disponible</dt>
                                <dd class="font-bold {{ $disponible <$ligne['demandee'] ? 'text-[--color-accent]' : 'text-[--color-primary]' }}">
                                    {{ $disponible }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Formulaire de modification d'attribution --}}
        <form action="{{ route('attributions.update', $attribution->id_attribution) }}" method="POST"
              x-data="{
                    lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
                    catalogue: {{ Illuminate\Support\Js::from($catalogueIndex) }},

                    infos(id) { return this.catalogue[id] ?? null; },

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
            @method('PUT')

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Ajustement des lignes d'attribution</h2>
                
                <div class="space-y-3">
                    <template x-for="(ligne, index) in lignes" :key="index">
                        <div class="grid gap-3 sm:grid-cols-12 items-end rounded-lg border border-slate-200 p-3">
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Appui</label>
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
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Quantité</label>
                                <input type="number" min="1" required :name="`lignes[${index}][quantite_attribuee]`"
                                       x-model="ligne.quantite_attribuee"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Coût unitaire</label>
                                <input type="number" min="0" step="0.01" :name="`lignes[${index}][cout_attribution]`"
                                       x-model="ligne.cout_attribution"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Remise %</label>
                                <input type="number" min="0" max="100" step="0.01" :name="`lignes[${index}][remise]`"
                                       x-model="ligne.remise"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Montant</label>
                                <input type="text" readonly
                                    class="mt-1 block w-full text-sm border-gray-300 bg-slate-50 font-semibold text-[--color-primary] rounded-md"
                                    :value="montant(ligne).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' F'">
                            </div>

                            <div class="sm:col-span-1">
                                <button type="button" @click="retirer(index)" :disabled="lignes.length === 1"
                                        class="w-full rounded-md border border-red-200 px-2 py-2 text-xs text-[--color-accent] hover:bg-red-50 disabled:opacity-40">
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
                    <p class="text-sm text-[--color-text-secondary]">
                        Total :
                        <span class="text-base font-bold text-[--color-primary]"
                              x-text="total.toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' F'"></span>
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-input-label for="commentaires" value="Observations & motivations du procès-verbal d'arbitrage" />
                <textarea id="commentaires" name="commentaires" rows="4"
                          class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm"
                          placeholder="Ex. : la quantité a été ajustée après contre-expertise.">{{ $commentaireInitial }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('attributions.show', $attribution->id_attribution) }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit"
                        class="rounded-md bg-[--color-button-first] px-5 py-2 text-sm font-semibold text-white hover:bg-[--color-button-secondary] transition-colors">
                    Mettre à jour l'attribution
                </button>
            </div>
        </form>

    </div>
</div>
@endsection