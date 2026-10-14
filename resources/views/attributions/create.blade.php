@extends($layout ?? 'layouts.aderiz')

@php
    // Le formulaire est pré-rempli avec ce qui a été demandé : la commission
    // ajuste les quantités et les coûts plutôt que de tout ressaisir.
    $lignesInitiales = old('lignes', $demande->lignes->map(function ($ligne) {
    $q =$ligne->quantite_demandee ?? 0;
    $c =$ligne->appui?->cout_unitaire ?? 0;
    $estEquipement = $ligne->appui?->estEquipement();
    return [
        'id_appui' => $ligne->id_appui,
        'quantite_attribuee' => $q,
        'estEquipement' => $estEquipement,
        'cout_attribution' => $c,
        'unite' => $ligne->appui?->uniteDosage->lib_unite ?? '',
        'remise' => 0,
        'montant_saisi' => $q * $c, // Initialisation
        'manuelle' => false,
    ];
    })->all());

    $catalogueIndex =$catalogue->mapWithKeys(function ($appui) {
                
        $estEquipement = $appui->estEquipement();

        return [$appui->id_appui => [
            'libelle' => $appui->libelle,
            'cout_unitaire' => $appui->cout_unitaire,
            'doseParHa' => $appui->dose_ha,
            'stock' => $appui->quantite_en_stock,
            'unite' => $appui->uniteDosage->lib_unite,
            'estEquipement' => $estEquipement,
            'ecologies' => $appui->typesSystemesProductions->pluck('id_type_systeme_production')->all(),
         ]];
    });

    $localitesStr = $demande->localites->pluck('lib_localite')->join(', ');$nomEntite = $demande->nom_structure ?? $demande->acteur?->nom;
@endphp

@section('titre-page', "Attribuer des appuis")

@section('content')
<div>
    <div class="max-w-5xl mx-auto space-y-6">
        {{-- En-tête du dossier --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h1 class="text-xl font-bold text-[--color-primary] sm:text-2xl">
                Demande {{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
            </h1>
            <p class="text-sm text-[--color-text-secondary] mt-1">
                Demandeur : {{ $nomEntite }}
            </p>
            <p class="text-sm text-[--color-text-secondary] mt-1">
                {{ $demande->typeActeur?->lib_type_acteur }}, 
                Localité(s) : {{ $localitesStr ?: 'Non spécifiée' }},
                {{ number_format(collect($ecologies)->sum('demandee'), 2, ',', ' ') }} ha
            </p>
        </div>


        {{-- Synthèse : demandé / en stock / déjà engagé --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Synthèse des quantités pour ce dossier</h2>

            <div class="mb-4 rounded-lg bg-slate-50 p-3 border border-slate-200">
                <p class="text-xs font-semibold text-[--color-primary] uppercase tracking-wider mb-2">Appuis initialement sollicités</p>
                <ul class="space-y-1 text-sm text-[--color-text-primary]">
                    @foreach ($demande->lignes as $ligne)
                        <li class="flex items-center justify-between">
                            <span>{{ $ligne->typeAppui?->lib_type_appui }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($synthese as $ligne)
                    @php $disponible = $ligne['stock'] -$ligne['engagee']; @endphp
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-[--color-text-primary]">{{ $ligne['libelle'] }}</p>
                        <dl class="mt-2 space-y-1 text-xs text-[--color-text-secondary]">
                            <div class="flex justify-between">
                                <dt>En stock</dt>
                                <dd class="font-medium text-slate-800">{{ $ligne['stock'] }} {{$ligne['unite'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Déjà attribué</dt>
                                <dd class="font-medium text-slate-800">{{ \App\Utils\Helper::quantite($ligne['engagee']) }} {{$ligne['unite'] }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-1">
                                <dt class="font-semibold">Disponible</dt>
                                <dd class="font-bold {{ $disponible <$ligne['demandee'] ? 'text-[--color-accent]' : 'text-[--color-primary]' }}">
                                    {{ \App\Utils\Helper::quantite($disponible) }} {{$ligne['unite'] }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Formulaire d'attribution --}}
        <form action="{{ route('attributions.store', ['idDemande' => $demande->id_demande, 'idSession'=>$idSession]) }}" method="POST"
              x-data="{
                    ecologies: {{ Illuminate\Support\Js::from(collect($ecologies)->map(fn ($e) => $e + ['saisie' => (float) old('superficies.'.$e['id_type'], $e['traitee'])])->all()) }},
                    lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
                    catalogue: {{ Illuminate\Support\Js::from($catalogueIndex) }},

                    infos(id) { return this.catalogue[id] ?? null; },

                    estSelectionne(idAppui, currentIndex) {
                        if (!idAppui) return false;
                        return this.lignes.some((ligne, idx) => idx !== currentIndex && String(ligne.id_appui) === String(idAppui));
                    },

                    // Calculs et changement appui

                    // 1. Calcul du montant brut (sans remise)
                    montantBrut(ligne) {
                        const q = Number(ligne.quantite_attribuee) || 0;
                        const c = Number(ligne.cout_attribution) || 0;
                        return q * c;
                    },

                    // 2. Calcul du montant final (avec remise)
                    montant(ligne) {
                        const brut = this.montantBrut(ligne);
                        const r = Number(ligne.remise) || 0;
                        return brut * (1 - r / 100);
                    },

                    onQuantiteSaisie(index) {
                        this.lignes[index].manuelle = true;
                        this.onQuantiteChange(index);
                    },

                    onQuantiteChange(index) {
                        const ligne = this.lignes[index];
                        this.onRemiseChange(index);
                        this.onMontantChange(index);
                    },

                    // 3. Quand la remise est modifiée dans son champ
                    onRemiseChange(index) {
                        const ligne = this.lignes[index];
                        // Met à jour la propriété montant_saisi pour refléter le montant recalculé
                        ligne.montant_saisi = Math.round(this.montant(ligne));
                    },

                    // 4. Quand le montant est modifié directement dans son champ
                    onMontantChange(index) {
                        const ligne = this.lignes[index];
                        const brut = this.montantBrut(ligne);
                        const nouveauMontant = Number(ligne.montant_saisi) || 0;

                        if (brut > 0) {
                            // Calcul de la remise correspondante en %
                            let remiseCalculee = ((brut - nouveauMontant) / brut) * 100;
                            
                            // On borne la remise entre 0% et 100% avec 2 décimales
                            remiseCalculee = Math.min(Math.max(remiseCalculee, 0), 100);
                            ligne.remise = Math.round(remiseCalculee * 100) / 100;
                        } else {
                            ligne.remise = 0;
                        }
                    },

                    // 5. Réinitialiser le montant_saisi lors du changement d'appui ou de quantité
                    // Quantité de départ d'un appui : dose à l'hectare × superficies traitées des écologies
                    // auxquelles il est lié (null : pas de calcul possible, la quantité reste telle quelle).
                    quantiteSuggeree(id) {
                        const appui = this.catalogue[id];
                        if (!appui || appui.estEquipement || !(Number(appui.doseParHa) > 0) || !appui.ecologies.length) return null;
                        const superficie = this.ecologies
                            .filter(e => appui.ecologies.includes(e.id_type))
                            .reduce((somme, e) => somme + (Number(e.saisie) || 0), 0);
                        return Math.round(Number(appui.doseParHa) * superficie * 100) / 100;
                    },

                    recalculer(index) {
                        const quantite = this.quantiteSuggeree(this.lignes[index].id_appui);
                        if (quantite === null) return;
                        this.lignes[index].quantite_attribuee = quantite;
                        this.onQuantiteChange(index);
                    },

                    // Une superficie traitée change : les quantités non ajustées à la main suivent.
                    onSuperficieChange() {
                        this.lignes.forEach((ligne, index) => { if (!ligne.manuelle) this.recalculer(index); });
                    },

                    onAppuiChange(index) {
                        const id = this.lignes[index].id_appui;
                        if (id && this.catalogue[id]) {
                            this.lignes[index].cout_attribution = this.catalogue[id].cout_unitaire ?? 0;
                            this.lignes[index].unite = this.catalogue[id].unite ?? '';
                            this.lignes[index].manuelle = false;
                            this.recalculer(index);
                            this.lignes[index].montant_saisi = Math.round(this.montant(this.lignes[index]));
                        }
                    },
                    // Calculs et changement appui

                    get total() {
                        return this.lignes.reduce((somme, l) => somme + this.montant(l), 0);
                    },

                    ajouter() { 
                        this.lignes.push({ id_appui: '', quantite_attribuee: 1, cout_attribution: 0, remise: 0, manuelle: false }); 
                    },
                    retirer(i) { 
                        if (this.lignes.length > 1) this.lignes.splice(i, 1); 
                    },
                }"
              class="space-y-6">
            @csrf

            @include('attributions.partials.superficies')

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="space-y-3">
                    <template x-for="(ligne, index) in lignes" :key="index">
                    {{--Lignes d'attributions--}}
                        <div class="grid gap-3 sm:grid-cols-12 items-end rounded-lg border border-slate-200 p-3">
                            {{-- Appui --}}
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Appui</label>
                                {{-- Appui select--}}
                                <select :name="`lignes[${index}][id_appui]`" 
                                        x-model="ligne.id_appui" 
                                        @change="onAppuiChange(index)"
                                        required
                                        class="mt-1 block w-full text-sm border-gray-300 bg-slate-50 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md font-semibold text-slate-800">
                                    {{-- Option du select--}}
                                    <option value="">Sélectionner un appui</option>
                                    @foreach ($catalogue as $appui)
                                        <option value="{{ $appui->id_appui }}" 
                                                :disabled="estSelectionne({{ $appui->id_appui }}, index)">
                                            {{ $appui->libelle }}
                                        </option>
                                    @endforeach
                                    {{-- Option du select--}}
                                </select>
                                {{-- Appui select--}}
                            </div>

                            {{-- Quantité --}}
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Quantité</label>
                                    <input type="number" min="0.0001" step="0.0001" required :name="`lignes[${index}][quantite_attribuee]`"
                                       x-model="ligne.quantite_attribuee"
                                       @input="onQuantiteSaisie(index)"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>
                            {{-- Quantité --}}

                            {{-- Unité --}}
                            <div class="sm:col-span-1">

                                <label class="block text-xs font-medium text-[--color-text-secondary]">Unité <br> dosage </label>
                                <input type="text"
                                       x-model="ligne.unite"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                                    
                            </div>
                            {{-- Unité --}}

                            {{-- Coût unitaire --}}
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Coût unitaire</label>
                                <input type="number" readonly min="0" step="0.01" :name="`lignes[${index}][cout_attribution]`"
                                       x-model="ligne.cout_attribution"
                                       class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>
                            {{-- Coût unitaire --}}
                            
                            {{-- Remise % --}}
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Remise %</label>
                                <input type="number" min="0" max="100" step="0.01" :name="`lignes[${index}][remise]`"
                                    x-model="ligne.remise"
                                    @input="onRemiseChange(index)"
                                    class="mt-1 block w-full text-sm border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>
                            {{-- Remise % --}}

                            {{-- Montant --}}
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-[--color-text-secondary]">Montant (FCFA)</label>
                                <input type="number" min="0" step="0.01"
                                    x-model="ligne.montant_saisi"
                                    @input="onMontantChange(index)"
                                    class="mt-1 block w-full text-sm border-gray-300 bg-slate-50 font-semibold text-[--color-primary] focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md">
                            </div>
                            {{-- Montant --}}

                            <div class="sm:col-span-1">
                                <button type="button" @click="retirer(index)" :disabled="lignes.length === 1"
                                        class="w-full rounded-md border border-red-200 px-2 py-2 text-xs text-[--color-accent] hover:bg-red-50 disabled:opacity-40">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>
                    {{--Lignes d'attributions--}}
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

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <x-input-label for="commentaires" value="Observations & motivations du procès-verbal d'arbitrage" />
                <textarea id="commentaires" name="commentaires" rows="4"
                          class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm"
                          placeholder="Ex. : la quantité a été estimée après visite de la parcelle.">{{ old('commentaires') }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('demandes.show', $demande->id_demande) }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit"
                        class="rounded-md bg-[--color-button-first] px-5 py-2 text-sm font-semibold text-white hover:bg-[--color-button-secondary] transition-colors">
                    Valider l'attribution
                </button>
            </div>
        </form>

        {{-- Section Rejet --}}
        <div x-data="{ ouvert: false }" class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-[--color-accent]">Rejeter le dossier</h2>
                    <p class="text-sm text-[--color-text-secondary]">Le motif saisi sera transmis au demandeur.</p>
                </div>
                <button type="button" @click="ouvert = !ouvert"
                        class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-[--color-accent] hover:bg-red-50">
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
                    <input type="hidden" name="id_demande" value="{{ $demande->id_demande }}">
                    <x-input-label for="motifs_rejet" value="Motifs du rejet" />
                    <textarea id="motifs_rejet" name="motifs_rejet" rows="3" required
                              class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                              placeholder="Ex. : superficie déclarée hors barème.">{{ old('motifs_rejet') }}</textarea>
                    <x-input-error :messages="$errors->get('motifs_rejet')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="rounded-md bg-[--color-accent] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                        Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection