@extends($layout ?? 'layouts.aderiz')

@section('content')

<div class="min-h-screen bg-[--color-background] px-6 py-8">

    <div class="mx-auto max-w-2xl">

        <h1 class="mb-2 text-2xl font-bold text-[--color-primary]">
            Enregistrer un approvisionnement
        </h1>

        <p class="mb-6 text-sm text-slate-500">
            L'entrée est ajoutée au stock de l'appui sélectionné.
        </p>

        @include('partials.flash')

        <form
            action="{{ route('approvisionnements.store') }}"
            method="POST"
            x-data="{
                quantite: {{ (int) old('quantite_ajoutee', 0) }}
            }"
            class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >

            @csrf

            {{-- Appui --}}
            <div
                x-data="{
                    recherche: '',
                    resultats: [],
                    ouvert: false,
                    chargement: false,
                    idAppui: '{{ old('id_appui', '') }}',
                    appuiSelectionne: null,
                    timeout: null,

                    rechercher() {
                        clearTimeout(this.timeout);

                        if (this.recherche.trim().length < 2) {
                            this.resultats = [];
                            this.ouvert = false;
                            return;
                        }

                        this.timeout = setTimeout(() => {
                            this.chargerResultats();
                        }, 300);
                    },

                    async chargerResultats() {
                        this.chargement = true;
                        this.ouvert = true;

                        try {
                            const response = await fetch(
                                '{{ route('approvisionnements.search-appuis') }}?q='
                                + encodeURIComponent(this.recherche)
                            );

                            if (!response.ok) {
                                throw new Error('Erreur HTTP ' + response.status);
                            }

                            this.resultats = await response.json();

                        } catch (error) {
                            console.error(
                                'Erreur lors de la recherche :',
                                error
                            );

                            this.resultats = [];

                        } finally {
                            this.chargement = false;
                        }
                    },

                    selectionner(appui) {
                        this.idAppui = appui.id;
                        this.appuiSelectionne = appui;
                        this.recherche = appui.libelle;
                        this.resultats = [];
                        this.ouvert = false;
                    }
                }"
                @click.outside="ouvert = false"
            >

                <x-input-label
                    for="recherche_appui"
                    value="Appui approvisionné"
                />

                {{-- Valeur réellement envoyée à Laravel --}}
                <input
                    type="hidden"
                    name="id_appui"
                    x-model="idAppui"
                >

                <div class="relative mt-1">

                    {{-- Champ de recherche --}}
                    <input
                        id="recherche_appui"
                        type="text"
                        x-model="recherche"
                        @input="rechercher()"
                        @focus="recherche.length >= 2 && (ouvert = true)"
                        autocomplete="off"
                        placeholder="Rechercher un appui..."
                        class="block w-full rounded-md border-gray-300 shadow-sm
                               focus:border-[--color-primary]
                               focus:ring-[--color-primary]"
                    >

                    {{-- Chargement --}}
                    <div
                        x-show="chargement"
                        x-cloak
                        class="absolute right-3 top-1/2 -translate-y-1/2"
                    >
                        <span class="text-xs text-slate-400">
                            Recherche...
                        </span>
                    </div>

                    {{-- Résultats --}}
                    <div
                        x-show="ouvert && resultats.length > 0"
                        x-cloak
                        class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto
                               rounded-md border border-slate-200 bg-white shadow-lg"
                    >

                        <template
                            x-for="appui in resultats"
                            :key="appui.id"
                        >

                            <button
                                type="button"
                                @click="selectionner(appui)"
                                class="block w-full border-b border-slate-100
                                       px-4 py-3 text-left last:border-0
                                       hover:bg-slate-50"
                            >

                                <span
                                    class="block font-medium text-slate-800"
                                    x-text="appui.libelle"
                                ></span>

                                <span
                                    class="block text-xs text-slate-500"
                                    x-text="appui.type"
                                ></span>

                                <span class="block text-xs text-slate-400">
                                    Stock :
                                    <span x-text="appui.stock"></span>
                                    <span x-text="appui.unite"></span>
                                </span>

                            </button>

                        </template>

                    </div>

                    {{-- Aucun résultat --}}
                    <div
                        x-show="
                            ouvert &&
                            !chargement &&
                            recherche.trim().length >= 2 &&
                            resultats.length === 0
                        "
                        x-cloak
                        class="absolute z-50 mt-1 w-full rounded-md
                               border border-slate-200 bg-white px-4 py-3
                               text-sm text-slate-500 shadow-lg"
                    >
                        Aucun appui trouvé.
                    </div>

                </div>

                {{-- Appui sélectionné --}}
                <div
                    x-show="appuiSelectionne"
                    x-cloak
                    class="mt-2 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600"
                >
                    Stock actuel :
                    <span
                        class="font-semibold"
                        x-text="appuiSelectionne?.stock"
                    ></span>

                    <span x-text="appuiSelectionne?.unite"></span>
                </div>

                <x-input-error
                    :messages="$errors->get('id_appui')"
                    class="mt-1"
                />

            </div>


            {{-- Quantité + date --}}
            <div class="grid gap-4 sm:grid-cols-2">

                {{-- Quantité --}}
                <div>

                    <x-input-label
                        for="quantite_ajoutee"
                        value="Quantité ajoutée"
                    />

                    <input
                        id="quantite_ajoutee"
                        name="quantite_ajoutee"
                        type="number"
                        min="1"
                        step="1"
                        x-model.number="quantite"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                               focus:border-[--color-primary]
                               focus:ring-[--color-primary]"
                    >

                    <x-input-error
                        :messages="$errors->get('quantite_ajoutee')"
                        class="mt-1"
                    />

                </div>


                {{-- Date --}}
                <div>

                    <x-input-label
                        for="date_approvisionnement"
                        value="Date de l'entrée"
                    />

                    <x-text-input
                        id="date_approvisionnement"
                        name="date_approvisionnement"
                        type="date"
                        class="mt-1 block w-full"
                        max="{{ now()->toDateString() }}"
                        :value="old(
                            'date_approvisionnement',
                            now()->toDateString()
                        )"
                        required
                    />

                    <x-input-error
                        :messages="$errors->get('date_approvisionnement')"
                        class="mt-1"
                    />

                </div>

            </div>


            {{-- Référence commande + origine --}}
            <div class="grid gap-4 sm:grid-cols-2">

                {{-- Référence commande --}}
                <div>

                    <x-input-label
                        for="reference_commande"
                        value="Référence de commande"
                    />

                    <x-text-input
                        id="reference_commande"
                        name="reference_commande"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('reference_commande')"
                        placeholder="Ex. BC-2026-00125"
                    />

                    <x-input-error
                        :messages="$errors->get('reference_commande')"
                        class="mt-1"
                    />

                </div>


                {{-- Origine --}}
                <div>

                    <x-input-label
                        for="origine"
                        value="Origine"
                    />

                    <x-text-input
                        id="origine"
                        name="origine"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('origine')"
                        placeholder="Ex. Fournisseur, don, transfert..."
                    />

                    <x-input-error
                        :messages="$errors->get('origine')"
                        class="mt-1"
                    />

                </div>

            </div>


            {{-- Aperçu du stock --}}
            <div
                x-data="{
                    stock: null,
                    unite: 'unité'
                }"
                x-cloak
            >

                <template x-if="$root.querySelector('[x-data]')">
                    <span></span>
                </template>

            </div>


            {{-- Actions --}}
            <div class="flex justify-end gap-3">

                <a
                    href="{{ route('approvisionnements.index') }}"
                    class="rounded-md border border-slate-300 px-4 py-2
                           text-sm text-slate-700 hover:bg-slate-50"
                >
                    Annuler
                </a>

                <button
                    type="submit"
                    class="rounded-md bg-[--color-primary] px-5 py-2
                           text-sm font-semibold text-white hover:opacity-90"
                >
                    Enregistrer l'entrée
                </button>

            </div>

        </form>

    </div>

</div>

@endsection