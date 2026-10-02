<?php

use App\Enums\PermissionEnum;
use App\Exports\AttributionsExport;
use App\Http\Controllers\ActeurController;
use App\Http\Controllers\ApprovisionnementController;
use App\Http\Controllers\AppuiController;
use App\Http\Controllers\AttributionController;
use App\Http\Controllers\CommissionAttributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Demande\DemandeRejetController;
use App\Http\Controllers\Demande\DemandeController;
use App\Http\Controllers\Editions\EditionArbitrageController;
use App\Http\Controllers\Editions\EditionAttributionController;
use App\Http\Controllers\EquipementController;
use App\Http\Controllers\EquipementRetourController;
use App\Http\Controllers\Mouvement\MouvementStockController;
use App\Http\Controllers\Notification\PushSubscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EnlevementController;
use App\Http\Controllers\ResourceSubscriptionController;
use App\Http\Controllers\SimulationSortieController;
use App\Http\Controllers\VitrineController;
use App\Livewire\AiAssistant;
use Illuminate\Support\Facades\Route;

// Vitrine publique : la démarche et le catalogue, sans authentification.
Route::get('/', VitrineController::class)->name('vitrine');

Route::middleware('auth')->group(function () {

    Route::get('/acteurs/rechercher', [App\Http\Controllers\ActeurController::class, 'rechercherParNomEtDate'])->name('acteurs.rechercher');
    
    Route::get('/editions/attributions', [EditionAttributionController::class, 'index'])->name('editions.attributions.index');
    Route::prefix('editions')->name('editions.')->group(function () {
        // Route de la page d'index de l'arbitrage
        Route::get('/arbitrages', [EditionArbitrageController::class, 'index'])
            ->name('arbitrages.index');

        // Route d'impression de la fiche d'arbitrage
        Route::match(['get', 'post'], '/attributions/imprimer-arbitrage', [EditionArbitrageController::class, 'imprimerArbitrage'])
            ->name('attributions.imprimer-arbitrage');
    });
    Route::get('/attributions/{idAttribution}', [AttributionController::class, 'show'])->name('attributions.show');

    Route::get('/attributions/{attribution}/edit', [AttributionController::class, 'edit'])->name('attributions.edit');
    Route::put('/attributions/{attribution}', [AttributionController::class, 'update'])->name('attributions.update');

    Route::post('/resources/{type}/{id}/toggle-alert', [ResourceSubscriptionController::class, 'toggle'])
            ->name('resources.toggle-alert');
            
// Routes pour l'attribution directe / don
    Route::get('/attributions/direct/create', [AttributionController::class, 'createDirect'])
        ->name('attributions.createDirect');

    Route::post('/attributions/direct', [AttributionController::class, 'storeDirect'])
        ->name('attributions.storeDirect');

// Route vers la vue du CRUD Appuis
    Route::get('/crud/appuis', function () {
        return view('crud.appuis.index');
    })->name('crud.appuis.index');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    
Route::get('/direction/assistant-ia', AiAssistant::class)->name('direction.ai-assistant');
    Route::get('/attributions', [AttributionController::class, 'index'])->name('attributions.index');
    Route::post('/attributions/valider', [AttributionController::class, 'valider'])->name('attributions.valider');
    Route::post('/attributions/annuler', [AttributionController::class, 'annuler'])->name('attributions.annuler');
    Route::post('/attributions/publier', [AttributionController::class, 'publier'])->name('attributions.publier');

    // DEMANDES
    Route::resource('demandes', DemandeController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/demandes/rejeter', DemandeRejetController::class)->name('demandes.rejeter');
    Route::post('/demandes/visiter', [DemandeController::class, 'visiter'])->name('demandes.visite.valider');
    Route::post('/demandes/soumettre/{id}', [DemandeController::class, 'soumettre'])->name('demandes.soumettre');
    Route::get('/demandes/pieces/{demande}', [DemandeController::class, 'pieces'])->name('demandes.pieces');
    Route::get('/demandes/pdf/{id}', [DemandeController::class, 'telechargerPdf'])->name('demandes.pdf');
    Route::get('/equipements', [EquipementController::class, 'index'])->name('equipements.index');

    // Route pour editer la demande
    Route::get('/demandes/{demande}/edit', [DemandeController::class, 'edit'])->name('demandes.edit');
    Route::put('/demandes/{demande}', [DemandeController::class, 'update'])->name('demandes.update');

    // Route pour supprimer la demande
    Route::delete('/demandes/{demande}', [DemandeController::class, 'destroy'])->name('demandes.destroy');

    Route::get('/mouvements-stocks', [MouvementStockController::class, 'index'])->name('mouvements-stocks.index');
    Route::get('/mouvements-stocks/{id}', [MouvementStockController::class, 'show'])->name('mouvements-stocks.show');
    
    // PROFIL
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/statistiques/export/attributions', [EditionAttributionController::class, 'exportAttributions'])
            ->name('stats.export.attributions');
    
    // Route d'enregistrement de l'abonnement PWA WebPush
    Route::post('/push-subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');

});

// ATTRIBUTIONS — l'arbitrage de la commission.
Route::middleware(['auth', 'permission:'.PermissionEnum::attribuerAppui->value])->group(function () {

    Route::get('/attributions/create/{idDemande}', [AttributionController::class, 'create'])->name('attributions.create');

    Route::post('/attributions/soumettre', [AttributionController::class, 'soumettre'])->name('attributions.soumettre');

    // Le brouillon d'arbitrage. Avant les routes à joker, sans quoi « simulation »
    // serait pris pour un identifiant d'attribution.
    Route::get('/attributions/simulation', [SimulationSortieController::class, 'index'])->name('attributions.simulation');
    Route::get('/attributions/simulation/etat', [SimulationSortieController::class, 'etat'])->name('attributions.simulation.etat');
    Route::post('/attributions/simulation', [SimulationSortieController::class, 'noter'])->name('attributions.simulation.noter');
    Route::delete('/attributions/simulation', [SimulationSortieController::class, 'reinitialiser'])->name('attributions.simulation.reinitialiser');

    Route::post('/attributions/{idDemande}', [AttributionController::class, 'store'])->name('attributions.store');
    Route::post('/attributions/{idDemande}/rejeter', [AttributionController::class, 'rejeter'])->name('attributions.rejeter');
    Route::delete('/attributions/{attribution}', [AttributionController::class, 'destroy'])->name('attributions.destroy');
});

// COMMISSIONS — ouverture et clôture des séances.
Route::middleware(['auth', 'permission:'.PermissionEnum::gererCommission->value])->group(function () {
    Route::get('/commissions', [CommissionAttributionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/synthese', [CommissionAttributionController::class, 'synthese'])->name('commissions.synthese');
    Route::get('/commissions/create', [CommissionAttributionController::class, 'create'])->name('commissions.create');

    Route::post('/commissions', [CommissionAttributionController::class, 'store'])->name('commissions.store');
    Route::get('/commissions/{id}', [CommissionAttributionController::class, 'show'])->name('commissions.show');
    Route::post('/commissions/{id}/cloturer', [CommissionAttributionController::class, 'cloturer'])->name('commissions.cloturer');
});

// STOCKS — le magasin : catalogue, entrées, et sorties sur bon.
Route::middleware(['auth', 'permission:'.PermissionEnum::gererStock->value])->group(function () {
    Route::get('/appuis', [AppuiController::class, 'index'])->name('appuis.index');
    Route::get('/appuis/create', [AppuiController::class, 'create'])->name('appuis.create');
    Route::post('/appuis', [AppuiController::class, 'store'])->name('appuis.store');

    Route::get('/approvisionnements', [ApprovisionnementController::class, 'index'])->name('approvisionnements.index');
    Route::get('/approvisionnements/create', [ApprovisionnementController::class, 'create'])->name('approvisionnements.create');
    Route::post('/approvisionnements', [ApprovisionnementController::class, 'store'])->name('approvisionnements.store');
    Route::get('/approvisionnements/recherche-appuis', [ApprovisionnementController::class, 'searchAppuis'])->name('approvisionnements.search-appuis');
    Route::get('/enlevements', [EnlevementController::class, 'index'])->name('enlevements.index');
    Route::get('/enlevements/{idAttribution}/create', [EnlevementController::class, 'create'])->name('enlevements.create');
    Route::patch('/attributions/{attribution}/destockage', [AttributionController::class, 'destocker'])->name('attributions.destocker');

    // EQUIPEMENT RETOUR //
    Route::get('/equipements/retour', [EquipementRetourController::class, 'create'])
        ->name('equipements.retour.create');

    Route::post('/equipements/retour', [EquipementRetourController::class, 'store'])
        ->name('equipements.retour.store');
});

// Le bon d'attribution est remis au demandeur : le DP le retire pour lui.
Route::middleware('auth')->group(function () {
    Route::get('/attributions/{attribution}/bon', [AttributionController::class, 'bon'])->name('attributions.bon');
});

require __DIR__.'/auth.php';
