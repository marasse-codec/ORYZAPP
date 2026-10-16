<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // L'ordre suit les dépendances : référentiels, puis comptes, puis
        // le jeu de démonstration.
        $this->call([
            ServiceSeeder::class,
            LocaliteSeeder::class,
            CampagneSeeder::class,
            TypeActeurSeeder::class,
            TypePieceSeeder::class,
            TypeSystemeProductionSeeder::class,
            TypeAppuiSeeder::class,
            AppuiSeeder::class,
            RolePermissionSeeder::class,
            UserSeed::class,
            DemandeSeeder::class,
            SessionCommissionSeeder::class,
            TypeMouvementStockSeed::class,
        ]);

        // Le jeu de présentation (deux années de dossiers cohérents), sauf pendant les tests,
        // qui partent d'un état neutre et sèment eux-mêmes ce dont ils ont besoin.
        if (! app()->runningUnitTests()) {
            $this->call(DemonstrationSeeder::class);
        }

        $this->realignerLesSequences();
    }

    /**
     * Certains seeders posent des identifiants à la main. MySQL et SQLite avancent alors seuls leur
     * compteur ; PostgreSQL non : la première insertion de l'application tomberait sur un identifiant
     * déjà pris. On cale donc chaque séquence sur le plus grand identifiant présent.
     */
    private function realignerLesSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $colonnes = DB::select("
            SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = current_schema() AND column_default LIKE 'nextval(%'
        ");

        foreach ($colonnes as $c) {
            DB::statement(sprintf(
                "SELECT setval(pg_get_serial_sequence('\"%1\$s\"', '%2\$s'), COALESCE((SELECT MAX(\"%2\$s\") FROM \"%1\$s\"), 0) + 1, false)",
                $c->table_name,
                $c->column_name,
            ));
        }
    }
}
