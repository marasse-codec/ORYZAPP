<?php

use App\Domain\Acces\Enums\PermissionEnum;
use App\Http\Controllers\Commission\AttributionController;
use App\Http\Controllers\Commission\CommissionAttributionController;
use App\Http\Controllers\Commission\EditionArbitrageController;
use App\Http\Controllers\Commission\EditionAttributionController;
use App\Http\Controllers\Commission\PaiementController;
use App\Http\Controllers\Commission\RendementController;
use App\Http\Controllers\Commission\EditionDemandeController;
use App\Http\Controllers\Stock\EditionStockController;
use App\Http\Controllers\Commission\SimulationSortieController;
use App\Http\Controllers\Demande\BrouillonDemandeController;
use App\Http\Controllers\Demande\DemandeController;
use App\Http\Controllers\Demande\DemandeRejetController;
use App\Http\Controllers\Demande\DemandeSerieController;
use App\Http\Controllers\Notification\PushSubscriptionController;
use App\Http\Controllers\Notification\ResourceSubscriptionController;
use App\Http\Controllers\PlanificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Stock\ApprovisionnementController;
use App\Http\Controllers\Stock\AppuiController;
use App\Http\Controllers\Stock\EnlevementController;
use App\Http\Controllers\Stock\EquipementController;
use App\Http\Controllers\Stock\EquipementRetourController;
use App\Http\Controllers\Stock\MouvementStockController;
use App\Http\Controllers\Suivi\DashboardController;
use App\Http\Controllers\Suivi\SauvegardeController;
use App\Http\Controllers\Suivi\StatistiqueController;
use App\Http\Controllers\VitrineController;
use Illuminate\Support\Facades\Route;

// Vitrine publique : la démarche et le catalogue, sans authentification.
Route::get('/', VitrineController::class)->name('vitrine');

// Planificateur déclenché de l'extérieur (hébergement sans cron) : jeton obligatoire, sinon 404.
Route::post('/planification/executer', PlanificationController::class)
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->middleware('throttle:10,1')
    ->name('planification.executer');

Route::middleware('auth')->group(function () {

    Route::get('/acteurs/rechercher', [App\Http\Controllers\Demande\ActeurController::class, 'rechercherParNomEtDate'])->name('acteurs.rechercher');
    
    Route::get('/editions/attributions', [EditionAttributionController::class, 'index'])->name('editions.attributions.index');
    Route::get('/editions/demandes', [EditionDemandeController::class, 'index'])->name('editions.demandes.index');
    Route::get('/attributions/{idAttribution}', [AttributionController::class, 'show'])->whereNumber('idAttribution')->name('attributions.show');
    // Règlements : encaissement d'une attribution publiée par qui tient la caisse (admin).
    Route::post('/attributions/{attribution}/paiements', [PaiementController::class, 'store'])
        ->whereNumber('attribution')
        ->middleware('permission:'.PermissionEnum::gererReglements->value)
        ->name('attributions.paiements.store');

    // Rendements : le DP transmet ce que les superficies de sa demande ont produit (garde dans la requête).
    Route::put('/attributions/{attribution}/rendements', [RendementController::class, 'update'])
        ->whereNumber('attribution')
        ->name('attributions.rendements.update');

    // Archives de sauvegarde : l'admin (ressources) les télécharge depuis l'administration.
    Route::get('/sauvegardes/{disque}/{nom}', [SauvegardeController::class, 'telecharger'])
        ->middleware('permission:'.PermissionEnum::gererRessources->value)
        ->name('sauvegardes.telecharger');

    Route::post('/resources/{type}/{id}/toggle-alert', [ResourceSubscriptionController::class, 'toggle'])
            ->name('resources.toggle-alert');
            
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    
    Route::get('/attributions', [AttributionController::class, 'index'])->name('attributions.index');

    // DEMANDES
    // La saisie en série précède la ressource : « serie » ne doit pas être pris pour un identifiant de demande.
    // Comme l'enregistrement unitaire, elle est ouverte à tout utilisateur connecté (un agent peut aussi créer une demande).
    // Sauvegarde automatique de la demande en cours de saisie.
    Route::get('/demandes/brouillon', [BrouillonDemandeController::class, 'show'])->name('demandes.brouillon.show');
    Route::put('/demandes/brouillon', [BrouillonDemandeController::class, 'save'])->name('demandes.brouillon.save');
    Route::delete('/demandes/brouillon', [BrouillonDemandeController::class, 'destroy'])->name('demandes.brouillon.destroy');
    Route::get('/demandes/serie', [DemandeSerieController::class, 'create'])->name('demandes.serie.create');
    Route::post('/demandes/serie', [DemandeSerieController::class, 'store'])->name('demandes.serie.store');
    Route::resource('demandes', DemandeController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/demandes/rejeter', DemandeRejetController::class)->name('demandes.rejeter');
    Route::post('/demandes/visiter', [DemandeController::class, 'visiter'])->name('demandes.visite.valider');
    Route::post('/demandes/soumettre/{id}', [DemandeController::class, 'soumettre'])->name('demandes.soumettre');
    Route::get('/demandes/pieces/{demande}', [DemandeController::class, 'pieces'])->name('demandes.pieces');
    Route::get('/demandes/pdf/{id}', [DemandeController::class, 'telechargerPdf'])->name('demandes.pdf');

    // Route pour editer la demande
    Route::get('/demandes/{demande}/edit', [DemandeController::class, 'edit'])->name('demandes.edit');
    Route::put('/demandes/{demande}', [DemandeController::class, 'update'])->name('demandes.update');


    
    // PROFIL
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/statistiques/export/attributions', [EditionAttributionController::class, 'exportAttributions'])
            ->name('stats.export.attributions');
    Route::get('/statistiques/export/demandes', [EditionDemandeController::class, 'exportDemandes'])
            ->name('stats.export.demandes');
    
    // Route d'enregistrement de l'abonnement PWA WebPush
    Route::post('/push-subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');

});

// ATTRIBUTIONS : l'arbitrage de la commission.
Route::middleware(['auth', 'permission:'.PermissionEnum::attribuerAppui->value])->group(function () {

    // Éditions : les fiches d'arbitrage papier, avant l'attribution.
    Route::get('/editions/arbitrages', [EditionArbitrageController::class, 'index'])->name('editions.arbitrages.index');
    Route::match(['get', 'post'], '/editions/arbitrages/imprimer', [EditionArbitrageController::class, 'imprimer'])->name('editions.arbitrages.imprimer');

    Route::get('/attributions/create/{idDemande}', [AttributionController::class, 'create'])->name('attributions.create');

    // Modification d'une attribution : seulement tant que la séance est ouverte (garde dans le contrôleur).
    Route::get('/attributions/{attribution}/edit', [AttributionController::class, 'edit'])->name('attributions.edit');
    Route::put('/attributions/{attribution}', [AttributionController::class, 'update'])->name('attributions.update');

    // Le brouillon d'arbitrage. Avant les routes à joker, sans quoi « simulation »
    // serait pris pour un identifiant d'attribution.
    Route::get('/attributions/simulation', [SimulationSortieController::class, 'index'])->name('attributions.simulation');
    Route::post('/attributions/simulation', [SimulationSortieController::class, 'noter'])->name('attributions.simulation.noter');
    Route::delete('/attributions/simulation', [SimulationSortieController::class, 'reinitialiser'])->name('attributions.simulation.reinitialiser');

    Route::post('/attributions/{idDemande}', [AttributionController::class, 'store'])->whereNumber('idDemande')->name('attributions.store');
    Route::delete('/attributions/{attribution}', [AttributionController::class, 'destroy'])->name('attributions.destroy');
});

// COMMISSIONS : ouverture et clôture des séances.
Route::middleware(['auth', 'permission:'.PermissionEnum::gererCommission->value])->group(function () {
    Route::get('/commissions', [CommissionAttributionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/synthese', [CommissionAttributionController::class, 'synthese'])->name('commissions.synthese');
    Route::get('/commissions/create', [CommissionAttributionController::class, 'create'])->name('commissions.create');

    Route::post('/commissions', [CommissionAttributionController::class, 'store'])->name('commissions.store');
    Route::get('/commissions/{id}', [CommissionAttributionController::class, 'show'])->name('commissions.show');
    Route::post('/commissions/{id}/cloturer', [CommissionAttributionController::class, 'cloturer'])->name('commissions.cloturer');
    // La publication des résultats d'une séance clôturée.
    Route::post('/attributions/publier', [AttributionController::class, 'publier'])->name('attributions.publier');
});

// STOCKS : le magasin : catalogue, entrées, et sorties sur bon.
Route::middleware(['auth', 'permission:'.PermissionEnum::gererStock->value])->group(function () {
    // Catalogue des appuis : consultation, ajout, modification, suppression.
    Route::resource('appuis', AppuiController::class)->except('show')->parameters(['appuis' => 'appui']);

    // Le parc de matériel et le registre des mouvements : consultation réservée au magasin.
    Route::get('/equipements', [EquipementController::class, 'index'])->name('equipements.index');
    Route::get('/mouvements-stocks', [MouvementStockController::class, 'index'])->name('mouvements-stocks.index');
    Route::get('/mouvements-stocks/{id}', [MouvementStockController::class, 'show'])->name('mouvements-stocks.show');

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

// ÉDITIONS DE STOCK : chaque service télécharge l'état des stocks des appuis qu'il fournit.
Route::middleware(['auth', 'permission:'.PermissionEnum::voirStocks->value])->group(function () {
    Route::get('/editions/stocks', [EditionStockController::class, 'index'])->name('editions.stocks.index');
    Route::get('/editions/stocks/export', [EditionStockController::class, 'exporter'])->name('editions.stocks.export');
});

// STATISTIQUES : réservées à l'administration.
Route::middleware(['auth', 'permission:'.PermissionEnum::voirStatistiques->value])->group(function () {
    Route::get('/statistiques', [StatistiqueController::class, 'index'])->name('statistiques.index');
});

// Le bon d'attribution est remis au demandeur : le DP le retire pour lui.
Route::middleware('auth')->group(function () {
    Route::get('/attributions/{attribution}/bon', [AttributionController::class, 'bon'])->name('attributions.bon');
    Route::get('/attributions/{attribution}/courrier', [AttributionController::class, 'courrier'])->name('attributions.courrier');
});

require __DIR__.'/auth.php';
