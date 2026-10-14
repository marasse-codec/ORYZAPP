<?php

namespace Tests\Feature;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Referentiel\Models\TypeSystemeProduction;
use App\Domain\Referentiel\Models\UniteDosage;
use App\Domain\Stock\Models\Appui;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Le catalogue des appuis : ajout, modification, suppression protégée, stock intouchable. */
class CatalogueAppuisTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerDonnees();
        $this->actingAs(User::role('gestionnaire-stock')->first());
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'libelle' => 'Semences certifiées',
            'id_type_appui' => 1,
            'id_unite' => UniteDosage::value('id_unite'),
            'dose_ha' => 0.05,
            'cout_unitaire' => 700,
            'seuil_alerte' => 40,
        ], $surcharge);
    }

    public function test_la_liste_se_filtre_par_libelle_et_par_type(): void
    {
        $appui = Appui::first();

        $this->get(route('appuis.index', ['q' => $appui->libelle]))->assertOk()->assertSee($appui->libelle);
        $this->get(route('appuis.index', ['q' => 'introuvable-xyz']))->assertOk()->assertSee('Aucun appui ne correspond');
        $this->get(route('appuis.index', ['type' => $appui->id_type_appui]))->assertOk();
    }

    public function test_le_gestionnaire_ajoute_un_appui_avec_dose_unite_seuil_et_systemes(): void
    {
        $systeme = TypeSystemeProduction::first();

        $this->post(route('appuis.store'), $this->donnees(['systemes' => [$systeme->id_type_systeme_production]]))
            ->assertRedirect(route('appuis.index'));

        $appui = Appui::where('libelle', 'Semences certifiées')->firstOrFail();
        $this->assertSame(40, $appui->seuil_alerte);
        $this->assertSame(0, $appui->quantite_en_stock);
        $this->assertTrue($appui->typesSystemesProductions->contains($systeme));
        $this->assertDatabaseHas('journals_actions', ['action' => ActionJournalEnum::appuiCree->value]);
    }

    public function test_les_champs_obligatoires_et_le_libelle_unique_sont_exiges(): void
    {
        $this->post(route('appuis.store'), ['libelle' => 'Incomplet'])
            ->assertSessionHasErrors(['id_type_appui', 'dose_ha', 'id_unite']);
        $this->assertDatabaseMissing('appuis', ['libelle' => 'Incomplet']);

        $this->post(route('appuis.store'), $this->donnees(['libelle' => Appui::first()->libelle]))
            ->assertSessionHasErrors('libelle');
    }

    public function test_la_modification_ne_touche_jamais_au_stock(): void
    {
        $appui = Appui::first();
        $stock = $appui->quantite_en_stock;

        $this->put(route('appuis.update', $appui), $this->donnees([
            'libelle' => $appui->libelle,
            'id_type_appui' => $appui->id_type_appui,
            'seuil_alerte' => 99,
            'quantite_en_stock' => 123456,
        ]))->assertRedirect(route('appuis.index'));

        $appui->refresh();
        $this->assertSame(99, $appui->seuil_alerte);
        $this->assertSame($stock, $appui->quantite_en_stock);
        $this->assertDatabaseHas('journals_actions', ['action' => ActionJournalEnum::appuiModifie->value]);
    }

    public function test_un_appui_neuf_se_supprime(): void
    {
        $this->post(route('appuis.store'), $this->donnees());
        $appui = Appui::where('libelle', 'Semences certifiées')->firstOrFail();

        $this->delete(route('appuis.destroy', $appui))->assertRedirect(route('appuis.index'));

        $this->assertDatabaseMissing('appuis', ['id_appui' => $appui->id_appui]);
        $this->assertDatabaseHas('journals_actions', ['action' => ActionJournalEnum::appuiSupprime->value]);
    }

    public function test_un_appui_utilise_ne_se_supprime_pas(): void
    {
        $appui = Appui::first();
        $this->ligne($this->demande(), $appui->id_type_appui, $appui->id_appui, 5);

        $this->delete(route('appuis.destroy', $appui))->assertSessionHas('error');

        $this->assertDatabaseHas('appuis', ['id_appui' => $appui->id_appui]);
    }

    public function test_un_autre_role_ne_peut_pas_gerer_le_catalogue(): void
    {
        $this->actingAs(User::role('dp')->first());

        $this->get(route('appuis.index'))->assertForbidden();
        $this->post(route('appuis.store'), $this->donnees())->assertForbidden();
        $this->delete(route('appuis.destroy', Appui::first()))->assertForbidden();
    }
}
