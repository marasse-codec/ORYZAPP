@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="mx-auto max-w-5xl">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">
                    Enregistrer un approvisionnement
                </h1>
                <p class="text-sm text-slate-500">
                    Saisissez l'entête de la commande et ajoutez un ou plusieurs appuis à approvisionner.
                </p>
            </div>
        </div>

        @include('partials.flash')

        <form 
            action="{{ route('approvisionnements.store') }}" 
            method="POST"
            x-data="formApprovisionnement()"
            class="space-y-6"
        >
            @csrf

            {{-- BLOC 1 : Entête de l'approvisionnement --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-2">
                    Informations générales
                </h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    {{-- Date d'entrée --}}
                    <div>
                        <x-input-label for="date_approvisionnement" value="Date de l'entrée *" />
                        <x-text-input 
                            id="date_approvisionnement" 
                            name="date_approvisionnement" 
                            type="date" 
                            class="mt-1 block w-full"
                            max="{{ now()->toDateString() }}"
                            :value="old('date_approvisionnement', now()->toDateString())" 
                            required 
                        />
                        <x-input-error :messages="$errors->get('date_approvisionnement')" class="mt-1" />
                    </div>

                    {{-- Référence Commande --}}
                    <div>
                        <x-input-label for="reference_commande" value="Référence de commande" />
                        <x-text-input 
                            id="reference_commande" 
                            name="reference_commande" 
                            type="text" 
                            class="mt-1 block w-full"
                            :value="old('reference_commande')" 
                            placeholder="Ex. BC-2026-00125" 
                        />
                        <x-input-error :messages="$errors->get('reference_commande')" class="mt-1" />
                    </div>

                    {{-- Origine --}}
                    <div>
                        <x-input-label for="origine" value="Origine / Fournisseur" />
                        <x-text-input 
                            id="origine" 
                            name="origine" 
                            type="text" 
                            class="mt-1 block w-full"
                            :value="old('origine')" 
                            placeholder="Ex. Don, Fournisseur X..." 
                        />
                        <x-input-error :messages="$errors->get('origine')" class="mt-1" />
                    </div>
                </div>
            </div>

            {{-- BLOC 2 : Lignes d'appuis (Tableau répétable) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="text-base font-semibold text-slate-800">
                        Appuis à approvisionner
                    </h2>
                    <button 
                        type="button" 
                        @click="ajouterLigne()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajouter un appui
                    </button>
                </div>

                <x-input-error :messages="$errors->get('lignes')" class="mt-1" />

                {{-- Tableau des lignes --}}
                <div class="overflow-visible">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 uppercase text-xs">
                            <tr>
                                <th class="p-3 rounded-l-lg">Appui *</th>
                                <th class="p-3 w-36">Stock Actuel</th>
                                <th class="p-3 w-44">Quantité Ajoutée *</th>
                                <th class="p-3 w-16 text-center rounded-r-lg">Action</th>
                            </tr>
                        </thead>

                        <template x-for="(ligne, index) in lignes" :key="ligne.key">
                            <tbody class="divide-y divide-slate-100 border-b border-slate-200">
                                {{-- Ligne principale --}}
                                <tr class="hover:bg-slate-50/50">
                                    {{-- Colonne Appui + Autocomplétion --}}
                                    <td class="p-3 align-top relative">
                                        <input 
                                            type="hidden" 
                                            :name="`lignes[${index}][id_appui]`" 
                                            x-model="ligne.id_appui" 
                                        >

                                        <div class="relative" @click.outside="ligne.ouvert = false">
                                            <input 
                                                type="text" 
                                                x-model="ligne.recherche"
                                                @input="rechercher(index)"
                                                @focus="ligne.recherche.length >= 2 && (ligne.ouvert = true)"
                                                placeholder="Rechercher un appui..."
                                                autocomplete="off"
                                                class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-[--color-primary] focus:ring-[--color-primary]"
                                            >

                                            {{-- Spinner --}}
                                            <div x-show="ligne.chargement" class="absolute right-3 top-2.5">
                                                <span class="text-xs text-slate-400">...</span>
                                            </div>

                                            {{-- Liste déroulante --}}
                                            <div 
                                                x-show="ligne.ouvert && ligne.resultats.length > 0" 
                                                x-cloak
                                                class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                                            >
                                                <template x-for="appui in ligne.resultats" :key="appui.id">
                                                    <button 
                                                        type="button" 
                                                        @click="selectionner(index, appui)"
                                                        class="block w-full px-4 py-2 text-left hover:bg-slate-50 border-b border-slate-100 last:border-0"
                                                    >
                                                        <span class="block font-medium text-slate-800" x-text="appui.libelle"></span>
                                                        <span class="block text-xs text-slate-400">
                                                            Stock: <span x-text="appui.stock"></span> <span x-text="appui.unite"></span>
                                                        </span>
                                                    </button>
                                                </template>
                                            </div>

                                            {{-- Aucun résultat --}}
                                            <div 
                                                x-show="ligne.ouvert && !ligne.chargement && ligne.recherche.trim().length >= 2 && ligne.resultats.length === 0" 
                                                x-cloak
                                                class="absolute z-50 mt-1 w-full rounded-md border border-slate-200 bg-white p-3 text-xs text-slate-500 shadow-lg"
                                            >
                                                Aucun appui trouvé.
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Colonne Stock actuel --}}
                                    <td class="p-3 align-middle text-slate-600">
                                        <template x-if="ligne.appuiSelectionne">
                                            <span class="inline-flex items-center gap-1 font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md text-xs">
                                                <span x-text="ligne.appuiSelectionne.stock"></span>
                                                <span x-text="ligne.appuiSelectionne.unite"></span>
                                            </span>
                                        </template>
                                        <template x-if="!ligne.appuiSelectionne">
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        </template>
                                    </td>

                                    {{-- Colonne Quantité ajoutée --}}
                                    <td class="p-3 align-top">
                                        <input 
                                            type="number" 
                                            :name="`lignes[${index}][quantite_ajoutee]`" 
                                            x-model.number="ligne.quantite_ajoutee"
                                            min="1" 
                                            step="1"
                                            required 
                                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-[--color-primary] focus:ring-[--color-primary]"
                                        >
                                    </td>

                                    {{-- Supprimer la ligne --}}
                                    <td class="p-3 align-middle text-center">
                                        <button 
                                            type="button" 
                                            @click="supprimerLigne(index)"
                                            :disabled="lignes.length === 1"
                                            class="text-slate-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-slate-400 transition-colors"
                                            title="Supprimer la ligne"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Sous-ligne pour la saisie des matricules si l'appui est un équipement --}}
                                <tr x-show="ligne.appuiSelectionne && !ligne.appuiSelectionne.est_intrant" x-cloak>
                                    <td colspan="4" class="p-3 bg-slate-50/70 border-b border-slate-200">
                                        <div class="space-y-2">
                                            <span class="text-xs font-semibold text-slate-700 block">
                                                Matricules / N° de série des équipements :
                                            </span>
                                            
                                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                                <template x-for="i in Number(ligne.quantite_ajoutee || 0)" :key="i">
                                                    <div>
                                                        <input 
                                                            type="text" 
                                                            :name="`lignes[${index}][matricules][]`"
                                                            :placeholder="`Matricule #${i}`"
                                                            class="w-full text-xs rounded border-slate-300 shadow-sm focus:border-[--color-primary] focus:ring-[--color-primary]"
                                                            required
                                                        >
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>

                {{-- Bouton d'ajout sous la table --}}
                <div class="pt-2">
                    <button 
                        type="button" 
                        @click="ajouterLigne()"
                        class="text-xs font-semibold text-[--color-primary] hover:underline inline-flex items-center gap-1"
                    >
                        + Ajouter un autre appui
                    </button>
                </div>
            </div>

            {{-- Actions globale --}}
            <div class="flex justify-end gap-3">
                <a 
                    href="{{ route('approvisionnements.index') }}" 
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                >
                    Annuler
                </a>
                <button 
                    type="submit" 
                    class="rounded-md bg-[--color-primary] px-6 py-2 text-sm font-semibold text-white hover:opacity-90 shadow-sm"
                >
                    Enregistrer l'approvisionnement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function formApprovisionnement() {
    return {
        lignes: [
            {
                key: Date.now(),
                id_appui: '',
                recherche: '',
                resultats: [],
                ouvert: false,
                chargement: false,
                appuiSelectionne: null,
                quantite_ajoutee: 1,
                timeout: null
            }
        ],

        ajouterLigne() {
            this.lignes.push({
                key: Date.now() + Math.random(),
                id_appui: '',
                recherche: '',
                resultats: [],
                ouvert: false,
                chargement: false,
                appuiSelectionne: null,
                quantite_ajoutee: 1,
                timeout: null
            });
        },

        supprimerLigne(index) {
            if (this.lignes.length > 1) {
                this.lignes.splice(index, 1);
            }
        },

        rechercher(index) {
            let ligne = this.lignes[index];
            clearTimeout(ligne.timeout);

            if (ligne.recherche.trim().length < 2) {
                ligne.resultats = [];
                ligne.ouvert = false;
                return;
            }

            ligne.timeout = setTimeout(async () => {
                ligne.chargement = true;
                ligne.ouvert = true;

                try {
                    const response = await fetch(
                        `{{ route('approvisionnements.search-appuis') }}?q=${encodeURIComponent(ligne.recherche)}`
                    );
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    ligne.resultats = await response.json();
                } catch (e) {
                    console.error(e);
                    ligne.resultats = [];
                } finally {
                    ligne.chargement = false;
                }
            }, 300);
        },

        selectionner(index, appui) {
            let ligne = this.lignes[index];
            ligne.id_appui = appui.id;
            ligne.appuiSelectionne = appui;
            ligne.recherche = appui.libelle;
            ligne.resultats = [];
            ligne.ouvert = false;
        }
    }
}
</script>
@endsection