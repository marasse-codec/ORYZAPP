<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypePiece;
use Illuminate\Database\Seeder;

class TypePieceSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            1 => 'Récépissé de la coopérative',
            2 => 'Agrément',
            3 => 'Liste des membres',
            4 => 'Demande-projet adressée à l\'ADERIZ',
            5 => 'Documents de constitution',
            6 => 'Plan de campagne',
        ];

        foreach ($types as $id => $libelle) {
            TypePiece::create(['id_type_piece' => $id, 'lib_type_piece' => $libelle]);
        }

        // Ce que chaque profil doit fournir. Le scan de la demande est exigé
        // de tous ; le reste dépend de la forme juridique.
        $exigences = [
            1 => [6 => false],                                    // Producteur individuel
            2 => [3 => true, 6 => false],                         // Groupement informel
            3 => [1 => true, 3 => true, 2 => false, 6 => false],  // Coopérative
            4 => [4 => true, 5 => true, 6 => false],              // Entreprise agricole
            5 => [4 => true, 5 => true, 6 => false],              // ONG
        ];

        foreach ($exigences as $idTypeActeur => $pieces) {
            $attachements = collect($pieces)
                ->mapWithKeys(fn ($obligatoire, $idTypePiece) => [$idTypePiece => ['est_obligatoire' => $obligatoire]])
                ->all();

            TypeActeur::find($idTypeActeur)?->typesPieces()->attach($attachements);
        }
    }
}
