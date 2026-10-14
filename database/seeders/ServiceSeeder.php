<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            1 => 'Service Semences',
            2 => 'Service Mecanisation et Equipement Agricole',
            3 => 'Service Infrastructures',
            4 => 'Service Travaux et Amenagements',
            5 => 'Service Transformation',
            6 => 'Service Commercialisation',
        ];

        foreach ($services as $id => $libelle) {
            Service::create(['id_service' => $id, 'lib_service' => $libelle]);
        }
    }
}
