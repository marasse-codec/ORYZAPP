<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\ApprovisionnementController;
use App\Http\Controllers\AppuiController;
use App\Http\Controllers\AttributionController;
use App\Http\Controllers\CommissionAttributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Demande\DemandeRejetController;
use App\Http\Controllers\Demande\DemandeController;
use App\Http\Controllers\EquipementController;
use App\Http\Controllers\EquipementRetourController;
use App\Http\Controllers\InstructionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecuperationController;
use App\Http\Controllers\SimulationSortieController;
use App\Http\Controllers\VitrineController;
use Illuminate\Support\Facades\Route;

// Vitrine publique : la démarche et le catalogue, sans authentification.
Route::get('/', VitrineController::class)->name('vitrine');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');

    Route::get('/attributions', [AttributionController::class, 'index'])->name('attributions.index');
    Route::post('/attributions/valider', [AttributionController::class, 'valider'])->name('attributions.valider');
    Route::post('/attributions/annuler', [AttributionController::class, 'annuler'])->name('attributions.annuler');
    Route::post('/attributions/publier', [AttributionController::class, 'publier'])->name('attributions.publier');

    // DEMANDES
    Route::resource('demandes', DemandeController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/demandes/rejeter', DemandeRejetController::class)->name('demandes.rejeter');
    Route::post('/demandes/soumettre/{id}', [DemandeController::class, 'soumettre'])->name('demandes.soumettre');
    Route::get('/demandes/pieces/{demande}', [DemandeController::class, 'pieces'])->name('demandes.pieces');
    Route::get('/demandes/pdf/{id}', [DemandeController::class, 'telechargerPdf'])->name('demandes.pdf');
    Route::get('/equipements', [EquipementController::class, 'index'])->name('equipements.index');

    // Route pour editer la demande
    Route::get('/demandes/{demande}/edit', [DemandeController::class, 'edit'])->name('demandes.edit');
    Route::put('/demandes/{demande}', [DemandeController::class, 'update'])->name('demandes.update');

    // Route pour supprimer la demande
    Route::delete('/demandes/{demande}', [DemandeController::class, 'destroy'])->name('demandes.destroy');

    // PROFIL
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// INSTRUCTION - chaque service se prononce sur les appuis qu'il fournit.
Route::middleware(['auth', 'permission:'.PermissionEnum::instruireDemande->value])->group(function () {

    Route::post(
        '/instructions/{idDemande}/demander-correction',
        [InstructionController::class, 'demanderCorrection']
    )->name('instructions.demander-correction');
    Route::get('/instructions', [InstructionController::class, 'index'])->name('instructions.index');

    // 1. Mettez la route spécifique AVANT les routes avec paramètres joker ({idDemande})
    Route::post('/instructions/prendre-en-charge-tous', [InstructionController::class, 'prendreEnChargeTous'])->name('instructions.prendre-en-charge-tous');

    // 2. Les autres routes spécifiques
    Route::get('/instructions/fiches', [InstructionController::class, 'fiches'])->name('instructions.fiches');

    // 3. Les routes dynamiques avec paramètres ({idDemande}) EN DERNIER
    Route::get('/instructions/{idDemande}', [InstructionController::class, 'show'])->name('instructions.show');
    Route::get('/instructions/{idDemande}/fiche', [InstructionController::class, 'fiche'])->name('instructions.fiche');
    Route::post('/instructions/{idDemande}', [InstructionController::class, 'store'])->name('instructions.store');
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

    Route::get('/attributions/{idAttribution}', [AttributionController::class, 'show'])->name('attributions.show');
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
    Route::get('/recuperations', [RecuperationController::class, 'index'])->name('recuperations.index');
    Route::get('/recuperations/{idAttribution}/create', [RecuperationController::class, 'create'])->name('recuperations.create');
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
