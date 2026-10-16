<?php

namespace Tests\Feature;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypePiece;
use App\Filament\Admin\Resources\TypeActeurResource\Pages\ManageTypesActeurs;
use App\Filament\Admin\Resources\TypePieceResource\Pages\ManageTypesPieces;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/**
 * Les pièces exigées (dont le scan de la demande écrite), réglées depuis
 * l'administration, et les cycles de l'année à venir que l'on peut déjà viser.
 */
class PiecesEtAnneeAVenirTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Une date fixe : 2026 est l'année en cours, 2027 l'année à venir.
        $this->travelTo('2026-10-16 10:00');
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function enAdministrateur(): void
    {
        $this->actingAs(User::role('admin')->first());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ------------------------------------------------------------ Pièces exigées

    public function test_le_scan_de_la_demande_ecrite_est_exige_de_tous_les_profils(): void
    {
        $scan = TypePiece::where('lib_type_piece', 'Scan de la demande écrite')->sole();

        $this->assertSame(TypeActeur::count(), $scan->typesActeurs()->count());
        $this->assertTrue($scan->typesActeurs->every(fn ($profil) => (bool) $profil->pivot->est_obligatoire));

        $this->actingAs(User::role('dp')->first())
            ->get(route('demandes.create'))
            ->assertOk()
            ->assertSee('Scan de la demande écrite');
    }

    public function test_ladministrateur_cree_une_piece_et_choisit_qui_doit_la_fournir(): void
    {
        $this->enAdministrateur();

        Livewire::test(ManageTypesPieces::class)
            ->callAction(CreateAction::class, [
                'lib_type_piece' => 'Attestation foncière',
                'profils' => [
                    ['id_type_acteur' => 3, 'est_obligatoire' => true],
                    ['id_type_acteur' => 4, 'est_obligatoire' => false],
                ],
            ])
            ->assertHasNoActionErrors();

        $piece = TypePiece::where('lib_type_piece', 'Attestation foncière')->sole();
        $this->assertEquals([3 => 1, 4 => 0], $piece->typesActeurs->mapWithKeys(fn ($p) => [$p->id_type_acteur => (int) $p->pivot->est_obligatoire])->all());

        // Le formulaire de demande la propose aussitôt aux coopératives.
        $pieces = TypeActeur::find(3)->typesPieces->pluck('lib_type_piece');
        $this->assertContains('Attestation foncière', $pieces);

        $trace = JournalAction::where('action', ActionJournalEnum::referentielCree->value)->latest('id')->first();
        $this->assertSame('Attestation foncière', $trace->details['lib_type_piece']);
    }

    public function test_ladministrateur_regle_les_pieces_dun_profil_et_le_journal_garde_lavant_apres(): void
    {
        $this->enAdministrateur();
        $individuel = TypeActeur::find(1);

        // La fenêtre arrive pré-remplie avec les pièces actuelles : on remplace toute la liste.
        Livewire::test(ManageTypesActeurs::class)
            ->mountTableAction(EditAction::class, $individuel)
            ->set('mountedTableActionsData.0.pieces', [
                ['id_type_piece' => 7, 'est_obligatoire' => true],
                ['id_type_piece' => 6, 'est_obligatoire' => true],
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertTrue((bool) $individuel->typesPieces()->find(6)->pivot->est_obligatoire);

        $ecart = JournalAction::where('action', ActionJournalEnum::referentielModifie->value)->latest('id')->first()->details['pieces_a_fournir'];
        $this->assertSame('Plan de campagne (facultative), Scan de la demande écrite', $ecart['avant']);
        $this->assertSame('Plan de campagne, Scan de la demande écrite', $ecart['apres']);
    }

    public function test_la_fenetre_de_modification_montre_les_pieces_actuelles(): void
    {
        $this->enAdministrateur();

        $donnees = Livewire::test(ManageTypesActeurs::class)
            ->mountTableAction(EditAction::class, TypeActeur::find(1))
            ->get('mountedTableActionsData.0.pieces');

        $this->assertEqualsCanonicalizing([7, 6], collect($donnees)->pluck('id_type_piece')->map(fn ($id) => (int) $id)->all());
    }

    // ------------------------------------------------------------ Année à venir

    public function test_on_peut_viser_un_cycle_de_lannee_a_venir(): void
    {
        $cycle = Campagne::deLAnnee(2027)->firstOrFail();
        $this->assertTrue($cycle->estAVenir());

        $this->actingAs(User::role('dp')->first())
            ->get(route('demandes.create'))
            ->assertOk()
            ->assertSee('2027 · année à venir')
            ->assertSee($cycle->libelleComplet());
    }

    public function test_un_cycle_a_venir_ne_devient_pas_le_cycle_en_cours(): void
    {
        // Entre deux cycles (ici après le Cycle 3, avant le Cycle 1 suivant) : on reste sur le dernier commencé.
        Campagne::whereKey($this->campagne->id_campagne)->delete();
        $this->travelTo('2026-12-15');

        $this->assertSame('Cycle 3 (2026)', Campagne::enCours()->libelleComplet());

        // Dans les statistiques, choisir l'année à venir montre son premier cycle.
        $this->actingAs(User::role('admin')->first())
            ->get(route('statistiques.index', ['annee' => 2027]))
            ->assertOk()
            ->assertViewHas('campagne', fn (Campagne $c) => $c->libelleComplet() === 'Cycle 1 (2027)');
    }
}
