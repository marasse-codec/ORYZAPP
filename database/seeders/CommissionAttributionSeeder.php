<?php

namespace Database\Seeders;

use App\Models\CommissionAttribution;
use Illuminate\Database\Seeder;

class CommissionAttributionSeeder extends Seeder
{
    public function run(): void
    {
        CommissionAttribution::firstOrCreate([
            'date_ouverture_commission' => now()->toDateString(),
        ]);
    }
}
