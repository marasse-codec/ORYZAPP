<?php

namespace Database\Seeders;

use App\Models\SessionCommission;
use Illuminate\Database\Seeder;

class SessionCommissionSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = [
            [
                'id_session' => 1,
                'resume' => 'Commission d\'attribution du 1er Semestre 2026 - Intrants et équipements légers.',
                'date_debut_session' => '2026-03-01',
            ]
        ];

        foreach ($sessions as $session) {
            SessionCommission::updateOrCreate(
                ['id_session' => $session['id_session']],
                [
                    'resume' => $session['resume'],
                    'date_debut_session' => $session['date_debut_session']
                ]
            );
        }
    }
}