@extends($layout ?? 'layouts.app')

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

        suggestionsActeurs: [],
        acteurTrouve: false,

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

        suivant() { if (this.step < this.totalSteps) this.step++; },
        precedent() { if (this.step > 1) this.step--; },
    }"
    class="min-h-screen bg-[--color-background] text-slate-800 pb-16"
>
    <header class="bg-white border-b border-slate-200 px-6 py-5 shadow-sm">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">Nouvelle demande d'appui</h1>
                    <p class="text-xs text-slate-500">Agence pour le Développement de la Filière Riz (ADERIZ)</p>
                </div>
                <div class="text-xs font-semibold text-[--color-primary] bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full w-fit">
                    Étape <span x-text="step"></span> sur <span x-text="totalSteps"></span>
                </div>
            </div>

            <ol class="flex items-center gap-2">
                @foreach (['Profil', 'Demandeur', 'Demande', 'Appuis & pièces'] as $index => $libelle)
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

    <div class="max-w-4xl mx-auto px-6 mt-6">
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

        <form method="POST" action="{{ route('demandes.store') }}" enctype="multipart/form-data"
              class="bg-white rounded-xl shadow-sm border border-slate-200">
            @csrf

            <input type="hidden" name="id_acteur" :value="idActeurExistant">

            {{-- Inclusion des composants de chaque étape --}}
            <x-demandes.demande-etape-profil :typesActeurs="$typesActeurs" />
            <x-demandes.demande-etape-demandeur />
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

                <button type="submit" x-show="step === totalSteps" x-cloak
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Soumettre la demande
                </button>
            </div>
        </form>
    </div>
</div>
@endsection