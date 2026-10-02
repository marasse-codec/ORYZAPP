<?php

namespace Database\Seeders;

use App\Models\Campagne;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            StatutDemandeSeed::class,
            StatutAttributionSeed::class,
            DemandeSeeder::class,
            SessionCommissionSeeder::class,
            TypeMouvementStockSeed::class,
        ]);
    }
}
