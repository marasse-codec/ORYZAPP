<?php

namespace Tests\Unit\Services;

use App\Models\Acteur;
use App\Models\Demande;
use App\Models\LigneDemande;
use App\Models\PieceDemande;
use App\Models\SystemeProduction;
use App\Services\CreationDemandeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CreationDemandeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function elle_execute_le_processus_de_creation_en_isolant_les_dependances()
    {
        // 1. Mock de la transaction DB : exécute la closure sans interroger SQL
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($callback) => $callback());

        // 2. Mocks des modèles Eloquent (interception des méthodes statiques)
        $acteurModel = Mockery::mock('alias:'.Acteur::class);
        $demandeModel = Mockery::mock('alias:'.Demande::class);
        $systemeProdModel = Mockery::mock('alias:'.SystemeProduction::class);
        $ligneDemandeModel = Mockery::mock('alias:'.LigneDemande::class);
        $pieceDemandeModel = Mockery::mock('alias:'.PieceDemande::class);

        // Simulation d'une instance Acteur renvoyée par firstOrNew
        $instanceActeur = Mockery::mock(Acteur::class)->makePartial();
        $instanceActeur->id_acteur = 42;
        $instanceActeur->email = 'ancien@example.com';
        $instanceActeur->shouldReceive('save')->once()->andReturnTrue();

        $acteurModel->shouldReceive('firstOrNew')
            ->once()
            ->with(['telephone' => '0700000000'])
            ->andReturn($instanceActeur);

        // Simulation d'une instance Demande renvoyée par create
        $instanceDemande = Mockery::mock(Demande::class)->makePartial();
        $instanceDemande->id_demande = 100;

        $demandeModel->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg['id_acteur'] === 42 && $arg['id_localite'] === 10;
            }))
            ->andReturn($instanceDemande);

        // 3. Vérification du filtrage métier (seule la superficie > 0 doit être créée)
        $systemeProdModel->shouldReceive('create')
            ->once()
            ->with([
                'id_demande' => 100,
                'id_type_systeme_production' => 1,
                'superficie' => 2.5,
            ]);

        // 4. Vérification de la création des lignes de demande
        $ligneDemandeModel->shouldReceive('create')->twice();

        // 5. Mock du fichier téléchargeable
        $fileMock = Mockery::mock(UploadedFile::class);
        $fileMock->shouldReceive('store')
            ->once()
            ->with('demandes/100', 'public')
            ->andReturn('demandes/100/cni.pdf');

        $pieceDemandeModel->shouldReceive('create')
            ->once()
            ->with([
                'id_demande' => 100,
                'id_type_piece' => 1,
                'chemin_fichier' => 'demandes/100/cni.pdf',
            ]);

        // Exécution du service
        $service = new CreationDemandeService;
        $resultat = $service->creer([
            'telephone' => '0700000000',
            'nom' => 'Kouassi',
            'email' => 'kouassi@example.com',
            'id_type_acteur' => 1,
            'id_localite' => 10,
            'debut_periode_mise_en_place' => '2026-10-01',
            'superficies' => [
                1 => 2.5,
                2 => 0, // Ignoré par la logique métier
            ],
            'lignes' => [
                ['id_appui' => 100, 'quantite_demandee' => 5],
                ['id_appui' => 101, 'quantite_demandee' => 2],
            ],
        ], [
            1 => $fileMock,
        ]);

        $this->assertSame($instanceDemande, $resultat);
    }
}
