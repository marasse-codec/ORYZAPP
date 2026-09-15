<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Acteur;
use App\Models\Appui;
use App\Models\Demande;
use App\Models\LigneDemande;
use App\Models\Localite;
use App\Models\Service;
use App\Models\StatutDemande;
use App\Models\SystemeProduction;
use App\Models\TypeActeur;
use App\Models\TypeAppui;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SNF06 : Authentification nominative et mot de passe haché.
     */
    public function test_mot_de_passe_est_bien_hache()
    {
        $user = User::factory()->create([
            'password' => bcrypt('MotDePasseSecr3t!'),
        ]);

        $this->assertTrue(Hash::check('MotDePasseSecr3t!', $user->password));
        $this->assertNotEquals('MotDePasseSecr3t!', $user->password);
    }

    /**
     * SNF07 : Habilitations par rôle (Accès interdit).
     */
    public function test_agent_ne_peut_acceder_qu_a_ses_droits()
    {
        Role::firstOrCreate(['name' => 'gestionnaire-stock']); // Assure l'existence du rôle
        
        // Un agent de centre tente d'accéder à la validation des attributions (Réservé au Validateur)
        $agentCentre = User::factory()->create();
        $agentCentre->assignRole([RoleEnum::gestionnaireStock]);

        $response = $this->actingAs($agentCentre)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/attributions/create/1');

        $response->assertStatus(403); // HTTP 403 Forbidden
    }

    /**
     * SNF08 : Traçabilité et horodatage dans le Journal des Actions.
     */
    public function test_action_est_horodatee_et_imputee_a_son_auteur()
    {
        
        Role::firstOrCreate(['name' => 'agent-service']);

        $service = Service::factory()->create();
        $typeAppui = TypeAppui::factory()->create(['id_service'=>$service->id_service]);
        $appui = Appui::factory()->create(['id_type_appui'=>$typeAppui->id_type_appui]);

        $agentService = User::factory()->create(['name' => 'Jean Dupont','id_service'=>$service->id_service]);
        $agentService->assignRole('agent-service');

        // Créer d'abord les dépendances nécessaires si la base est vide
        $localite = Localite::factory()->create();
        $statut = StatutDemande::factory()->create();
        $acteur = Acteur::factory()->create();
        $typeActeur = TypeActeur::factory()->create();

        // Puis créer la demande en lui rattachant ces éléments valides
        $demande = Demande::factory()->create([
            'id_acteur' => $acteur->id_acteur,
            'id_localite' => $localite->id_localite,
            'id_statut_demande' => $statut->id_statut_demande,
            'id_user' => $agentService->id,
            'id_type_acteur' => $typeActeur->id_type_acteur
        ]);

        $lignesDemandes = LigneDemande::factory()->create(['id_demande'=>$demande->id_demande, 'id_appui'=>$appui->id_appui]);

        // Le validateur rejette la demande (cette action doit déclencher un log dans le code du Controller)
        $this->actingAs($agentService)->patch("demandes/rejeter", [
            'id_demande' => $demande->id_demande, 
            'motif_rejet' => 'Dossier incomplet'
        ]);

        // Vérification du Journal (SNF08)
        $this->assertDatabaseHas('journals_actions', [
            'user_id' => $agentService->id,
            'action' => 'REJET_DEMANDE_SERVICE',
            'cible_type' => Demande::class,
            'cible_id' => $demande->id_demande
        ]);
    }
}
