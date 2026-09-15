<!-- cette interface aide à créer une réponse à une demande d'appui -->

@extends('layouts.aderiz')

@section('content')

<div class="max-w-4xl mx-auto bg-white p-8 rounded shadow" 
     x-data="{ 
        openPanel: false, 
        appuisMateriels: {{ $appuisMateriels->map(function($a) { 
                $a->qty_selection = 0; 
                $a->date_debut_location = date('Y-m-d'); 
                $a->date_fin_location = date('Y-m-d'); 
                return $a; 
            })->toJson() }},
        panierArticlesNonLocatifs: [],
        panierArticlesLocatifs: [],
        ajouter(appui) {
            // Vérifie si la quantité est valide
            if (appui.qty_selection > 0) {
                // Ajoute au panier
                if(!appui.est_louable){
                    // Supprime si déjà présent pour mettre à jour la quantité
                    this.panierArticlesNonLocatifs = this.panierArticlesNonLocatifs.filter(a => a.id_appui !== appui.id_appui);

                    this.panierArticlesNonLocatifs.push({ 
                        id_appui: appui.id_appui, 
                        quantite: appui.qty_selection 
                    });
                }else {
                    // Supprime si déjà présent pour mettre à jour la quantité
                    this.panierArticlesLocatifs = this.panierArticlesLocatifs.filter(a => a.id_appui !== appui.id_appui);

                    this.panierArticlesLocatifs.push({ 
                        id_appui: appui.id_appui, 
                        quantite: appui.qty_selection,
                        date_debut_location: appui.date_debut_location,
                        date_fin_location: appui.date_fin_location
                    });
                }
            }
        },
        retirer(appui) {
            if(!appui.est_louable){
                this.panierArticlesNonLocatifs = this.panierArticlesNonLocatifs.filter(a => a.id_appui !== appui.id_appui);
            }else{
                this.panierArticlesLocatifs = this.panierArticlesLocatifs.filter(a => a.id_appui !== appui.id_appui);
            }
        }
     }">

    <!-- Bouton d'ouverture -->
    <div class="mb-4 text-right">
        <button type="button" @click="openPanel = true" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
            + Ajouter des Appuis
        </button>
    </div>


    <h1 class="text-2xl font-bold mb-6">Créer une réponse</h1>

    <form action="{{ route('reponse.store') }}" method="POST">
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @csrf
        <div class="mb-4">
            <label for="demande_id" class="block text-gray-700 font-bold mb-2">Demande</label>
            <select name="reponse_id_demande" id="demande_id" class="w-full border border-gray-300 p-2 rounded">
                @foreach($demandes as $demande)
                    <option value="{{ $demande->id_demande }}">Demande de {{ $demande->acteur->nom }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="reponses_appuis_locatifs" :value="JSON.stringify(panierArticlesLocatifs)">
        <input type="hidden" name="reponses_appuis_non_locatifs" :value="JSON.stringify(panierArticlesNonLocatifs)">
        <div class="mb-4">
            <label for="date_reponse" class="block text-gray-700 font-bold mb-2">Date de Réponse</label>
            <input name="reponse_date" type="date" readonly value="{{ date('Y-m-d') }}" id="date_reponse" class="w-full border border-gray-300 p-2 rounded" required>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Créer la réponse</button>
        </div>
    </form>

    <!-- Interface coulissante (Slide-over) -->
    <div x-show="openPanel" class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div class="absolute inset-0 bg-gray-500 bg-opacity-75" @click="openPanel = false"></div>
        <div class="absolute right-0 top-0 h-full w-[80%] bg-white shadow-xl p-6 overflow-y-auto">
            <h2 class="text-xl font-bold mb-4">Sélectionner les appuis</h2>
            
            <div class="mt-4 p-4 bg-gray-100">
                <p>Articles non locatifs dans le panier: <span x-text="panierArticlesNonLocatifs.length"></span></p>
                <p>Articles locatifs dans le panier: <span x-text="panierArticlesLocatifs.length"></span></p>
            </div>
            
            <div class="lg:grid grid-cols-3">
                <template x-for="appui in appuisMateriels" :key="appui.id_appui">
                    <div class="border p-3 mb-2 rounded">
                        <p class="font-bold" x-text="appui.appui.libelle"></p>
                        <p class="text-sm text-gray-500">Stock max: <span x-text="appui.quantite"></span></p>
                        
                        <input type="number" 
                            min="0" 
                            :max="appui.quantite" 
                            x-model.number="appui.qty_selection" 
                            class="w-full border p-1 my-2" 
                            placeholder="Quantité">
                        
                        <div x-show="appui.est_louable" class="mb-4 grid grid-cols-2">
                            <label for="date_reponse" class="block text-gray-700 font-bold mb-2">Date de debut location</label>
                            <input x-model="appui.date_debut_location" type="date" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" name="reponse_date" class="w-full border p-1 my-2" required>
                            <label for="date_reponse" class="block text-gray-700 font-bold mb-2">Date de fin de location</label>
                            <input x-model="appui.date_fin_location" type="date" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" name="reponse_date" class="w-full border p-1 my-2" required>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="ajouter(appui)" class="bg-blue-500 text-white px-2 py-1 text-sm rounded">Ajouter</button>
                            <button type="button" @click="retirer(appui)" class="bg-red-500 text-white px-2 py-1 text-sm rounded">Retirer</button>
                        </div>
                    </div>
                </template>
            </div>
            
            <button @click="openPanel = false" class="mt-4 w-full bg-gray-800 text-white p-2 rounded">Valider la sélection</button>
        </div>
    </div>
    <!-- À placer juste avant la fin de la div parente -->
    <div class="mt-4 p-4 bg-gray-100">
        <p>Intrants dans le panier: <span x-text="panierArticlesNonLocatifs.length"></span></p>
        <p>Equipemement dans le panier: <span x-text="panierArticlesLocatifs.length"></span></p>
    </div>
</div>

    
@endsection