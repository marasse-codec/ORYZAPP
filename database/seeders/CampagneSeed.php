<?php

namespace Database\Seeders;

use App\Models\Campagne;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class CampagneSeed extends Seeder
{
    public function run(): void
    {
        Campagne::create(
            [
                'lib_campagne' => 'Pior',
                'date_debut_campagne' => '2026-01-01',
                'date_fin_campagne' => '2026-07-01'
            ]
        );
    }
}
