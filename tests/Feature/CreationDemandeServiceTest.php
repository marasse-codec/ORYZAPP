<?php

namespace Tests\Feature;

use App\Models\Acteur;
use App\Models\Demande;
use App\Models\PieceDemande;
use App\Services\CreationDemandeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreationDemandeServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreationDemandeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreationDemandeService;
        // Création des données de référence obligatoires pour satisfaire les clés étrangères
        $this->creerDonneesDeReference();
    }

    private function creerDonneesDeReference(): void
    {
        // Ajustez les noms de tables et colonnes selon vos migrations réelles
        DB::table('types_acteurs')->insertOrIgnore(['id_type_acteur' => 1, 'lib_type_acteur' => 'Producteur']);
        DB::table('localites')->insertOrIgnore(['id_localite' => 10, 'lib_localite' => 'Abidjan']);
        DB::table('types_systemes_production')->insertOrIgnore(['id_type_systeme_production' => 1, 'lib_type_systeme_production' => 'Irrigué']);
        DB::table('appuis')->insertOrIgnore([
            ['id_appui' => 100, 'libelle' => 'Engrais'],
            ['id_appui' => 101, 'libelle' => 'Semences'],
        ]);
        DB::table('types_pieces')->insertOrIgnore(['id_type_piece' => 1, 'libelle' => 'CNI']);
    }

    /** @test */
    public function test_cree_une_demande_complete_avec_toutes_ses_relations(): void
    {
        // 1. ARRANGE : Préparation des faux fichiers et données de formulaire
        Storage::fake('public');

        $donnees = [
            'telephone' => '0700000000',
            'nom' => 'Kouassi',
            'email' => 'kouassi@example.com',
            'id_type_acteur' => 1,
            'id_localite' => 10,
            'debut_periode_mise_en_place' => '2026-10-01',
            'superficies' => [
                1 => 2.5, // Valide (> 0)
                2 => 0,   // Doit être ignoré (<= 0)
            ],
            'lignes' => [
                ['id_appui' => 100, 'quantite_demandee' => 5],
                ['id_appui' => 101, 'quantite_demandee' => 2],
            ],
        ];

        $pieces = [
            1 => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
        ];

        // 2. ACT : Exécution du service
        $demande = $this->service->creer($donnees, $pieces);

        // 3. ASSERT : Vérifications
        // A. Vérification de la demande et de l'acteur
        $this->assertInstanceOf(Demande::class, $demande);
        $this->assertDatabaseHas('acteurs', [
            'telephone' => '0700000000',
            'nom' => 'Kouassi',
        ]);
        $this->assertDatabaseHas('demandes', [
            'id_demande' => $demande->id_demande,
            'id_localite' => 10,
        ]);

        // B. Vérification des superficies (seule la valeur > 0 est enregistrée)
        $this->assertDatabaseHas('systeme_productions', [
            'id_demande' => $demande->id_demande,
            'id_type_systeme_production' => 1,
            'superficie' => 2.5,
        ]);
        $this->assertDatabaseMissing('systeme_productions', [
            'id_demande' => $demande->id_demande,
            'id_type_systeme_production' => 2,
        ]);

        // C. Vérification des lignes enregistrées
        $this->assertDatabaseCount('ligne_demandes', 2);

        // D. Vérification de la pièce jointe BDD et du fichier physique stocké
        $piece = PieceDemande::first();
        $this->assertNotNull($piece);
        $this->assertEquals(1, $piece->id_type_piece);
        Storage::disk('public')->assertExists($piece->chemin_fichier);
    }

    /** @test */
    public function test_reutilise_un_acteur_existant_et_met_a_jour_ses_donnees()
    {
        // ARRANGE : Création préalable d'un acteur
        $acteurExistant = Acteur::create([
            'telephone' => '0700000000',
            'nom' => 'Ancien Nom',
            'email' => 'ancien@example.com',
        ]);

        $donnees = [
            'telephone' => '0700000000', // Même numéro
            'nom' => 'Nouveau Nom',     // Nom mis à jour
            'email' => 'nouveau@example.com',
            'id_type_acteur' => 1,
            'id_localite' => 10,
            'debut_periode_mise_en_place' => '2026-10-01',
        ];

        // ACT
        $demande = $this->service->creer($donnees);

        // ASSERT : L'acteur n'est pas dupliqué mais mis à jour
        $this->assertEquals($acteurExistant->id_acteur, $demande->id_acteur);
        $this->assertDatabaseCount('acteurs', 1);
        $this->assertDatabaseHas('acteurs', [
            'id_acteur' => $acteurExistant->id_acteur,
            'nom' => 'Nouveau Nom',
        ]);
    }

    /** @test */
    public function test_annule_toute_la_transaction_en_cas_derreur_sql()
    {
        // ARRANGE : Données provoquant une erreur (ex: clé étrangère manquante ou valeur null)
        $donnees = [
            'telephone' => '0700000000',
            'nom' => 'Kouassi',
            'id_type_acteur' => 1,
            'id_localite' => 10,
            'debut_periode_mise_en_place' => '2026-10-01',
            'lignes' => [
                ['id_appui' => null, 'quantite_demandee' => 5], // Ligne invalide
            ],
        ];

        // ACT & ASSERT
        try {
            $this->service->creer($donnees);
        } catch (\Throwable $e) {
            // L'exception est levée
        }

        // Vérification du rollback : l'acteur et la demande ne doivent pas exister en BDD
        $this->assertDatabaseCount('acteurs', 0);
        $this->assertDatabaseCount('demandes', 0);
    }
}
