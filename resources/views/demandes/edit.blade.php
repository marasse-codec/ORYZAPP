@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Modifier la demande')

@section('content')
<div
    x-data="{
        // Après un refus du serveur, on rouvre l'étape du premier champ en erreur.
        step: {{ $errors->any() ? \App\Domain\Demande\Support\ReglesDemande::etapeEnErreur($errors->keys()) : 1 }},
        totalSteps: 5,
        
        /* --- ÉTAPE 1 : PROFIL & PIÈCES --- */
        idTypeActeur: '{{ old('id_type_acteur', $demande->id_type_acteur ?? $demande->acteur?->id_type_acteur ?? '') }}',
        piecesParTypeActeur: {{ Illuminate\Support\Js::from($piecesParTypeActeur) }},
        typesActeursMeta: {{ Illuminate\Support\Js::from($typesActeursMeta) }},

        /* --- ÉTAPE 2 : DEMANDEUR --- */
        idActeurExistant: '{{ old('id_acteur', $demande->id_acteur ?? '') }}',
        nom: '{{ old('nom', $demande->acteur?->nom ?? $demande->acteur?->nom_acteur ?? '') }}',
        dateNaissance: '{{ old('date_naissance', $demande->acteur?->date_naissance ? \Carbon\Carbon::parse($demande->acteur->date_naissance)->format('Y-m-d') : '') }}',
        telephone: '{{ old('telephone', $demande->acteur?->telephone ?? '') }}',
        email: '{{ old('email', $demande->acteur?->email ?? '') }}',
        nomStructure: '{{ old('nom_structure', $demande->nom_structure ?? $demande->acteur?->nom_structure ?? '') }}',
        suggestionsActeurs: [],
        demandePrecedente: '',
        acteurTrouve: {{ $demande->id_acteur ? 'true' : 'false' }},

        /* --- ÉTAPE 3 : DEMANDE, LOCALITÉS & SUPERFICIES --- */
        selectedDepartement: '{{ old('selected_departement', $premierDepartementId) }}',
        selectedLocalites: {{ Illuminate\Support\Js::from(array_map('intval', (array) old('localites', $demande->localites->pluck('id_localite')->toArray()))) }},
        localitesList: {{ Illuminate\Support\Js::from($localites) }},
        superficies: {{ Illuminate\Support\Js::from($superficiesInitiales) }},

        /* --- ÉTAPE 4 : APPUIS --- */
        lignes: {{ Illuminate\Support\Js::from($lignesInitiales) }},

        /* --- FONCTIONS & ACTIONS --- */
        async rechercherActeurParNomEtDate() {
            if (!this.nom || this.nom.length < 2 || !this.dateNaissance) {
                this.suggestionsActeurs = [];
                return;
            }
            if (this.acteurTrouve) return;

            try {
                let response = await fetch(`{{ route('acteurs.rechercher') }}?nom=${encodeURIComponent(this.nom)}&date_naissance=${encodeURIComponent(this.dateNaissance)}`);
                this.suggestionsActeurs = await response.json();
            } catch (error) {
                console.error('Erreur lors de la recherche croisée:', error);
            }
        },

        selectionnerActeur(acteur) {
            this.idActeurExistant = acteur.id_acteur;
            this.nom = acteur.nom;
            this.dateNaissance = acteur.date_naissance;
            this.telephone = acteur.telephone ?? '';
            this.email = acteur.email ?? '';
            // Le type d'acteur et la structure ne sont pas repris : ils valent pour chaque demande
            // (on peut venir en coopérative une fois, en individuel la suivante).
            this.demandePrecedente = acteur.precedente ?? '';
            this.suggestionsActeurs = [];
            this.acteurTrouve = true;
        },

        reinitialiserActeur() {
            this.idActeurExistant = '';
            this.nom = '';
            this.dateNaissance = '';
            this.telephone = '';
            this.email = '';
            this.nomStructure = '';
            this.acteurTrouve = false;
            this.suggestionsActeurs = [];
        },

        get aBesoinNomStructure() {
            if (!this.idTypeActeur || !this.typesActeursMeta[this.idTypeActeur]) return false;
            return this.typesActeursMeta[this.idTypeActeur].est_structure;
        },

        get piecesAttendues() {
            return this.piecesParTypeActeur[this.idTypeActeur] ?? [];
        },

        ajouterLigne() {
            this.lignes.push({ id_type_appui: '', id_appui: '', quantite: '' });
        },

        retirerLigne(index) {
            if (this.lignes.length > 1) this.lignes.splice(index, 1);
        },

        // On ne passe à l'étape suivante que si ses champs obligatoires sont remplis.
        suivant() {
            if (!window.FormulaireEtapes?.etapeValide(this.$root, this.step)) return;
            if (this.step < this.totalSteps) this.step++;
        },
        precedent() { if (this.step > 1) this.step--; },

        // À l'envoi, un champ manquant ramène à l'étape où il se trouve.
        verifierAvantEnvoi(evenement) {
            const invalide = window.FormulaireEtapes?.premierInvalide(evenement.target);
            if (!invalide) return true;
            evenement.preventDefault();
            if (invalide.etape) this.step = invalide.etape;
            this.$nextTick(() => invalide.champ.reportValidity());
            return false;
        },
    }"
    class="mx-auto w-full max-w-4xl space-y-6"
>
    <header class="rounded-xl border border-[--color-border] bg-white p-4 shadow-sm sm:p-6">
        <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                <div>
                    <h1 class="text-xl font-bold text-[--color-primary] sm:text-2xl">Modification de la demande n°{{ $demande->id_demande }}</h1>
                    <p class="text-xs text-[--color-muted]">Agence pour le Développement de la Filière Riz (ADERIZ)</p>
                </div>
                <div class="text-xs font-semibold text-[--color-primary] bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full w-fit">
                    Étape <span x-text="step"></span> sur <span x-text="totalSteps"></span>
                </div>
            </div>

            <ol class="flex items-center gap-2">
                @foreach (['Demandeur', 'Profil', 'Demande', 'Appuis & pièces', 'Visite terrain'] as $index => $libelle)
                    <li class="flex-1">
                        <div class="h-1.5 rounded-full transition-colors"
                             :class="step >= {{ $index + 1 }} ? 'bg-[--color-primary]' : 'bg-slate-200'"></div>
                        <span class="mt-1.5 block text-[11px] font-medium"
                              :class="step >= {{ $index + 1 }} ? 'text-[--color-primary]' : 'text-slate-400'">
                            {{ $index + 1 }}. {{$libelle }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>
    </header>

    <div>
        
        {{-- Motif de renvoi / observation --}}
        @if (!empty($demande->commentaire_rejet))
            <div class="mb-5 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg text-amber-800 text-sm shadow-sm">
                <p class="font-bold">Motif de renvoi / observation :</p>
                <p class="mt-1">{{ $demande->commentaire_rejet }}</p>
            </div>
        @endif

        {{-- Erreurs de validation --}}
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">La demande n'a pas pu être mise à jour :</p>
                <ul class="mt-1.5 list-disc list-inside text-sm text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('demandes.update', $demande->id_demande) }}" enctype="multipart/form-data"
              novalidate @submit="verifierAvantEnvoi($event)"
              class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
            @csrf
            @method('PUT')
            <p class="text-xs text-slate-500">Les champs marqués <span class="text-red-600">*</span> sont obligatoires.</p>

            <input type="hidden" name="id_acteur" :value="idActeurExistant">

            {{-- Étape 1 : Demandeur --}}
            <div x-show="step === 1">
                <x-demandes.demande-etape-demandeur :demande="$demande" />
            </div>

            {{-- Étape 2 : Profil --}}
            <div x-show="step === 2" x-cloak>
                <x-demandes.demande-etape-profil :typesActeurs="$typesActeurs" :demande="$demande" />
            </div>

            {{-- Étape 3 : Demande & Localités --}}
            <div x-show="step === 3" x-cloak>
                <x-demandes.demande-etape-infos :localites="$localites" :campagnes="$campagnes" :typesSystemesProductions="$typesSystemesProductions" :demande="$demande" />
            </div>

            {{-- Étape 4 : Appuis & Pièces --}}
            <div x-show="step === 4" x-cloak>
                <x-demandes.demande-etape-appuis :typesAppuis="$typesAppuis" :appuisDisponibles="$appuisDisponibles" :demande="$demande" />
            </div>

            {{-- Étape 5 : Visite de terrain --}}
            <div x-show="step === 5" x-cloak>
                <x-demandes.demande-etape-visites :demande="$demande" />
            </div>

            {{-- Navigation --}}
            <div class="flex items-center justify-between border-t border-slate-200 pt-4 mt-6">
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
                    Mettre à jour la demande
                </button>
            </div>
        </form>
    </div>
</div>
@endsection