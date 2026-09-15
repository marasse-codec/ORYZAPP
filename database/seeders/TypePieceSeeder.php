<?php

namespace Database\Seeders;

use App\Models\TypeActeur;
use App\Models\TypePiece;
use Illuminate\Database\Seeder;

class TypePieceSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            1 => 'Scan de la demande',
            2 => 'Récépissé de la coopérative',
            3 => 'Agrément',
            4 => 'Liste des membres',
            5 => 'Demande-projet adressée à l\'ADERIZ',
            6 => 'Documents de constitution',
        ];

        foreach ($types as $id => $libelle) {
            TypePiece::create(['id_type_piece' => $id, 'lib_type_piece' => $libelle]);
        }

        // Ce que chaque profil doit fournir. Le scan de la demande est exigé
        // de tous ; le reste dépend de la forme juridique.
        $exigences = [
            1 => [1 => true],                                    // Producteur individuel
            2 => [1 => true, 4 => true],                         // Groupement informel
            3 => [1 => true, 2 => true, 4 => true, 3 => false],  // Coopérative
            4 => [1 => true, 2 => true, 4 => true, 3 => false],  // Société coopérative
            5 => [1 => true, 5 => true, 6 => true],              // Entreprise agricole
            6 => [1 => true, 5 => true, 6 => true],              // ONG
        ];

        foreach ($exigences as $idTypeActeur => $pieces) {
            $attachements = collect($pieces)
                ->mapWithKeys(fn ($obligatoire, $idTypePiece) => [$idTypePiece => ['est_obligatoire' => $obligatoire]])
                ->all();

            TypeActeur::find($idTypeActeur)?->typesPieces()->attach($attachements);
        }
    }
}
