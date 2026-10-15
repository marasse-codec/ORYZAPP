@extends($layout ?? 'layouts.aderiz')

@php
    $piecesParTypeActeur = $typesActeurs->mapWithKeys(fn ($type) => [$type->id_type_acteur => $type->typesPieces->map(fn ($piece) => [
            'id' => $piece->id_type_piece,
            'libelle' => $piece->lib_type_piece,
            'obligatoire' => (bool) $piece->pivot->est_obligatoire,
        ])->values(),
    ]);

    $typesActeursMeta = $typesActeurs->mapWithKeys(fn ($type) => [$type->id_type_acteur => [
            'libelle' => $type->lib_type_acteur,
            'est_structure' => !in_array(
                mb_strtolower(trim($type->lib_type_acteur)), 
                ['individu', 'groupement non formel', 'producteur individuel']
            )
        ]
    ]);

    $appuisDisponibles = $typesAppuis->flatMap(function($type) {
        return $type->appuis->map(fn($appui) => [
            'id' => $appui->id_appui,
            'libelle' => $appui->libelle,
            'id_type_appui' => $appui->id_type_appui,
            'unite' => $appui->uniteDosage?->lib_unite ?? ''
        ]);
    })->values();

    $lignesInitiales = old('lignes', [['id_type_appui' => '', 'id_appui' => '', 'quantite_demandee' => '']]);    
@endphp

@section('titre-page', 'Nouvelle demande')

@section('content')
<div
    x-data="{
        step: 1,
        totalSteps: 4,
        idTypeActeur: '{{ old('id_type_acteur', '') }}',
        lignes: {{ Illuminate\Support\Js::from(array_values($lignesInitiales)) }},
        piecesParTypeActeur: {{ Illuminate\Support\Js::from($piecesParTypeActeur) }},
        typesActeursMeta: {{ Illuminate\Support\Js::from($typesActeursMeta) }},
        superficies: {},
        selectedDepartement: '',
        selectedLocalites: {{ Illuminate\Support\Js::from(array_map('intval', (array) old('localites', isset($demande) ?$demande->localites->pluck('id_localite')->toArray() : []))) }},
        localitesList: {{ Illuminate\Support\Js::from($localites) }},

        idActeurExistant: '{{ old('id_acteur', '') }}',
        nom: '{{ old('nom', '') }}',
        dateNaissance: '{{ old('date_naissance', '') }}',
        telephone: '{{ old('telephone', '') }}',
        email: '{{ old('email', '') }}',
        nomStructure: '{{ old('nom_structure', '') }}',
        periode_debut_mise_en_place: '{{ old('periode_debut_mise_en_place', isset($demande) ? $demande->periode_debut_mise_en_place : '') }}',
        periode_fin_mise_en_place: '{{ old('periode_fin_mise_en_place', isset($demande) ? $demande->periode_fin_mise_en_place : '') }}',

        suggestionsActeurs: [],
        // Ce que l'acteur retrouvé doit encore sur ses attributions précédentes : s'il doit, l'enregistrement sera refusé.
        resteDu: 0,
        acteurTrouve: false,

        // Sauvegarde automatique de la saisie (brouillon serveur, fichiers exclus).
        brouillonPropose: null,
        brouillonStatut: '',
        minuteurBrouillon: null,
        champsLibres: ['id_campagne', 'periode_debut_mise_en_place', 'periode_fin_mise_en_place'],

        async init() {
            // Après un rejet de validation, les valeurs old() font foi : on ne propose rien.
            if (this.nom) return;
            try {
                const reponse = await fetch(`{{ route('demandes.brouillon.show') }}`, { headers: { 'Accept': 'application/json' } });
                this.brouillonPropose = reponse.status === 200 ? await reponse.json() : null;
            } catch (e) { this.brouillonPropose = null; }
            // Hors ligne (ou saisie restée sur l'appareil) : on propose la copie locale.
            this.brouillonPropose ??= window.BrouillonLocal?.lire('demande');
            this.$watch('step', () => this.planifierSauvegarde());
            // Retour du réseau : la copie locale part au serveur.
            window.addEventListener('online', () => this.sauvegarderBrouillon());
        },

        // Valeurs à conserver : état Alpine + champs non liés (campagne, période).
        instantane() {
            const champs = {};
            this.champsLibres.forEach(n => { champs[n] = this.$root.querySelector(`[name='${n}']`)?.value ?? ''; });
            return {
                step: this.step, idTypeActeur: this.idTypeActeur, idActeurExistant: this.idActeurExistant,
                nom: this.nom, dateNaissance: this.dateNaissance, telephone: this.telephone, email: this.email,
                nomStructure: this.nomStructure, lignes: this.lignes, selectedLocalites: this.selectedLocalites,
                superficies: this.superficies, champs,
            };
        },

        planifierSauvegarde() {
            clearTimeout(this.minuteurBrouillon);
            this.minuteurBrouillon = setTimeout(() => this.sauvegarderBrouillon(), 1500);
        },

        async sauvegarderBrouillon() {
            // Formulaire vierge : rien à conserver (et on ne masque pas un brouillon encore proposé).
            if (this.brouillonPropose || (!this.nom && !this.telephone && !this.idTypeActeur)) return;
            try {
                const reponse = await fetch(`{{ route('demandes.brouillon.save') }}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ donnees: this.instantane() }),
                });
                if (!reponse.ok) throw new Error();
                const { enregistre_le } = await reponse.json();
                this.brouillonStatut = 'Brouillon enregistré à ' + new Date(enregistre_le).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                window.BrouillonLocal?.effacer('demande'); // le serveur a la saisie : la copie locale ne sert plus
            } catch (e) {
                // Pas de réseau : la saisie est gardée sur l'appareil, elle repartira au retour de la connexion.
                this.brouillonStatut = window.BrouillonLocal?.sauver('demande', this.instantane())
                    ? 'Hors ligne : brouillon gardé sur cet appareil'
                    : 'Brouillon non enregistré (connexion ?)';
            }
        },

        reprendreBrouillon() {
            const d = this.brouillonPropose.donnees;
            ['idTypeActeur', 'idActeurExistant', 'nom', 'dateNaissance', 'telephone', 'email', 'nomStructure']
                .forEach(k => { this[k] = d[k] ?? ''; });
            this.lignes = d.lignes?.length ? d.lignes : this.lignes;
            this.selectedLocalites = d.selectedLocalites ?? [];
            this.superficies = d.superficies ?? {};
            this.acteurTrouve = !!d.idActeurExistant;
            this.step = Math.min(d.step || 1, this.totalSteps);
            // Les champs non liés ne sont rendus qu'après la mise à jour de l'état.
            this.$nextTick(() => Object.entries(d.champs ?? {}).forEach(([n, v]) => {
                const champ = this.$root.querySelector(`[name='${n}']`);
                if (champ) { champ.value = v; champ.dispatchEvent(new Event('input')); }
            }));
            this.brouillonPropose = null;
        },

        async ignorerBrouillon() {
            this.brouillonPropose = null;
            window.BrouillonLocal?.effacer('demande');
            await fetch(`{{ route('demandes.brouillon.destroy') }}`, {
                method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
        },

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
            this.nomStructure = acteur.nom_structure ?? '';
            if (acteur.id_type_acteur) {
                this.idTypeActeur = acteur.id_type_acteur;
            }
            this.suggestionsActeurs = [];
            this.resteDu = Number(acteur.reste_du ?? 0);
            this.acteurTrouve = true;
        },

        reinitialiserActeur() {
            this.idActeurExistant = '';
            this.nom = '';
            this.dateNaissance = '';
            this.telephone = '';
            this.email = '';
            this.nomStructure = '';
            this.resteDu = 0;
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

        suivant() { if (this.step < this.totalSteps) this.step++; },
        precedent() { if (this.step > 1) this.step--; },
    }"
    class="mx-auto w-full max-w-4xl space-y-6"
>
    <div x-show="resteDu > 0" x-cloak role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
        Cet acteur n'a pas fini de régler ses attributions précédentes
        (reste dû : <strong x-text="new Intl.NumberFormat('fr-FR').format(resteDu) + ' F'"></strong>).
        Une nouvelle demande sera refusée tant que le solde n'est pas réglé.
    </div>

    <header class="rounded-xl border border-[--color-border] bg-white p-4 shadow-sm sm:p-6">
        <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                <div>
                    <h1 class="text-xl font-bold text-[--color-primary] sm:text-2xl">Nouvelle demande d'appui</h1>
                    <p class="text-xs text-[--color-muted]">Agence pour le Développement de la Filière Riz (ADERIZ)</p>
                </div>
                <div class="text-xs font-semibold text-[--color-primary] bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full w-fit">
                    Étape <span x-text="step"></span> sur <span x-text="totalSteps"></span>
                </div>
            </div>

            <ol class="flex items-center gap-2">
                @foreach (['Demandeur', 'Profil', 'Demande', 'Appuis & pièces'] as $index => $libelle)
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
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">La demande n'a pas pu être enregistrée :</p>
                <ul class="mt-1.5 list-disc list-inside text-sm text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div x-show="brouillonPropose" x-cloak class="mb-5 flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-amber-900">
                Une saisie interrompue a été retrouvée
                (<span x-text="brouillonPropose && new Date(brouillonPropose.enregistre_le).toLocaleString('fr-FR')"></span>).
                Les pièces justificatives devront être rejointes à nouveau.
            </p>
            <div class="flex gap-2">
                <button type="button" @click="reprendreBrouillon()" class="rounded-md bg-[--color-primary] px-3 py-1.5 text-sm font-semibold text-white hover:opacity-90">Reprendre</button>
                <button type="button" @click="ignorerBrouillon()" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-white">Ignorer</button>
            </div>
        </div>

        <form method="POST" action="{{ route('demandes.store') }}" enctype="multipart/form-data"
              @input="planifierSauvegarde()" @change="planifierSauvegarde()"
              @submit="if (!navigator.onLine) { $event.preventDefault(); sauvegarderBrouillon(); alert('Vous êtes hors ligne : la demande est gardée sur cet appareil. Envoyez-la quand la connexion sera revenue.'); }"
              class="bg-white rounded-xl shadow-sm border border-slate-200">
            @csrf

            <input type="hidden" name="id_acteur" :value="idActeurExistant">

            {{-- Inclusion des composants de chaque étape --}}
            <x-demandes.demande-etape-demandeur />
            <x-demandes.demande-etape-profil :typesActeurs="$typesActeurs" />
            <x-demandes.demande-etape-infos :localites="$localites" :campagnes="$campagnes" :typesSystemesProductions="$typesSystemesProductions" />
            <x-demandes.demande-etape-appuis :typesAppuis="$typesAppuis" :appuisDisponibles="$appuisDisponibles" />

            {{-- Navigation --}}
            <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4">
                <button type="button" @click="precedent()" x-show="step > 1" x-cloak
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Précédent
                </button>
                <span x-show="step === 1"></span>

                <button type="button" @click="suivant()" x-show="step < totalSteps"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Suivant
                </button>

                <span class="text-xs text-slate-400" x-text="brouillonStatut" x-cloak></span>

                <button type="submit" x-show="step === totalSteps" x-cloak
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Enregistrer la demande
                </button>
            </div>
        </form>
    </div>
</div>
@endsection