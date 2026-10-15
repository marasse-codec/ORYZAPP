<?php

namespace Tests\Feature;

use App\Domain\Referentiel\Models\TypeSystemeProduction;
use App\Domain\Stock\Models\Appui;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_equipement_sert_toutes_les_ecologies(): void
    {
        $this->seed(DatabaseSeeder::class);

        $nbEcologies = TypeSystemeProduction::count();
        $equipements = Appui::whereHas('typeAppui', fn ($q) => $q->where('est_intrant', false))->withCount('typesSystemesProductions')->get();

        $this->assertGreaterThanOrEqual(6, $equipements->count());
        $this->assertTrue($equipements->every(fn ($appui) => $appui->types_systemes_productions_count === $nbEcologies));
    }

    public function test_les_intrants_gardent_leurs_ecologies(): void
    {
        $this->seed(DatabaseSeeder::class);

        $wita = Appui::where('libelle', 'Semences WITA 9')->firstOrFail();

        $this->assertEqualsCanonicalizing([1, 2], $wita->typesSystemesProductions()->pluck('types_systemes_productions.id_type_systeme_production')->all());
    }

    public function test_la_vitrine_presente_le_parcours_jusqu_au_rendement(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('De la demande au retour de rendement')
            ->assertSee('Transmettre le rendement', false);
    }
}
