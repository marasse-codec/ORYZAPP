<?php

namespace Tests\Feature;

use App\Domain\Acces\Enums\RoleEnum;
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
     * SNF08 : traçabilité et horodatage dans le Journal des Actions.
     * Couvert en détail par JournalisationTest.
     */
    public function test_action_est_horodatee_et_imputee_a_son_auteur()
    {
        $this->withoutVite();
        \Illuminate\Support\Facades\Notification::fake();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $dp = User::role('dp')->first();
        $this->actingAs($dp);

        $action = \App\Domain\Journal\Models\JournalAction::enregistrer(\App\Domain\Journal\Enums\ActionJournalEnum::demandeCreee, \App\Domain\Demande\Models\Demande::first());

        $this->assertSame($dp->id, $action->user_id);
        $this->assertNotNull($action->created_at);
    }
}
