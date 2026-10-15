<?php

namespace Tests\Feature;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Referentiel\Models\Localite;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Services\CatalogueAppuiService;
use App\Filament\Admin\Resources\LocaliteResource\Pages\ManageLocalites;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** L'historisation : connexions, valeurs avant/après, chronologie d'un dossier. */
class HistorisationTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function trace(ActionJournalEnum $action): ?JournalAction
    {
        return JournalAction::where('action', $action->value)->latest('id')->first();
    }

    public function test_connexions_echecs_et_deconnexions_sont_journalises(): void
    {
        $dp = User::role('dp')->first();

        $this->post('/login', ['email' => $dp->email, 'password' => 'mauvais']);
        $echec = $this->trace(ActionJournalEnum::connexionEchouee);
        $this->assertNull($echec->user_id);                              // personne n'est connecté
        $this->assertSame($dp->email, $echec->details['email']);
        $this->assertArrayNotHasKey('password', $echec->details);       // le mot de passe n'est jamais noté
        $this->assertNotNull($echec->adresse_ip);

        $this->post('/login', ['email' => $dp->email, 'password' => 'password']);
        $this->assertSame($dp->id, $this->trace(ActionJournalEnum::connexion)->user_id);
        $this->assertNotNull($dp->refresh()->derniere_connexion_le);

        $this->post('/logout');
        $this->assertSame($dp->id, $this->trace(ActionJournalEnum::deconnexion)->user_id);
    }

    public function test_les_ecarts_ne_gardent_que_ce_qui_change(): void
    {
        $this->assertSame(
            ['prix' => ['avant' => '1000.00', 'apres' => 1200]],
            Ecarts::entre(['prix' => '1000.00', 'dose' => '2', 'updated_at' => 'hier'], ['prix' => 1200, 'dose' => 2.0, 'updated_at' => 'aujourd\'hui']),
        );
    }

    public function test_la_modification_dun_appui_garde_lancien_prix(): void
    {
        $this->actingAs(User::role('admin')->first());
        $appui = Appui::first();
        $ancien = (float) $appui->cout_unitaire;

        app(CatalogueAppuiService::class)->modifier($appui, [
            'libelle' => $appui->libelle, 'id_type_appui' => $appui->id_type_appui, 'id_unite' => $appui->id_unite,
            'dose_ha' => $appui->dose_ha, 'cout_unitaire' => $ancien + 500, 'seuil_alerte' => $appui->seuil_alerte,
            'systemes' => $appui->typesSystemesProductions()->pluck('types_systemes_productions.id_type_systeme_production')->all(),
        ]);

        $details = $this->trace(ActionJournalEnum::appuiModifie)->details;
        $this->assertEquals($ancien, (float) $details['cout_unitaire']['avant']);
        $this->assertEquals($ancien + 500, (float) $details['cout_unitaire']['apres']);
        $this->assertArrayNotHasKey('libelle_avant', $details);
    }

    public function test_un_referentiel_modifie_dans_ladministration_est_historise(): void
    {
        $this->actingAs(User::role('admin')->first());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $localite = Localite::find(1);
        $ancienNom = $localite->lib_localite;

        Livewire::test(ManageLocalites::class)
            ->callTableAction(EditAction::class, $localite, ['lib_localite' => 'Nouveau nom', 'id_departement' => $localite->id_departement])
            ->assertHasNoTableActionErrors();

        $details = $this->trace(ActionJournalEnum::referentielModifie)->details;
        $this->assertSame(['avant' => $ancienNom, 'apres' => 'Nouveau nom'], $details['lib_localite']);
    }

    public function test_la_fiche_dune_demande_montre_sa_chronologie(): void
    {
        $dp = User::role('dp')->first();
        $this->actingAs($dp);
        $demande = $this->demandeSoumise();
        JournalAction::enregistrer(ActionJournalEnum::demandeSoumise, $demande);
        $attribution = $this->attribuer($demande, $this->seance(), [1 => 2]);
        JournalAction::enregistrer(ActionJournalEnum::attributionCreee, $attribution);
        $attribution->delete();
        JournalAction::enregistrer(ActionJournalEnum::attributionSupprimee, $attribution, ['motif' => 'erreur de saisie']);

        $this->get(route('demandes.show', $demande->id_demande))
            ->assertOk()
            ->assertSee('Chronologie du dossier')
            ->assertSeeInOrder(['Demande soumise', 'Attribution créée', 'Attribution supprimée'])
            ->assertSee('motif : erreur de saisie');
    }
}
