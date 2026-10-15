<?php

namespace App\Domain\Demande\Services;

use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\Localite;
use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Referentiel\Models\TypeSystemeProduction;
use Illuminate\Support\Collection;

/**
 * Les données de référence qu'il faut au formulaire de demande (création et
 * modification) : listes déroulantes et petites tables de correspondance que
 * l'écran manipule côté navigateur. Lecture seule, sans effet sur les données.
 */
class FormulaireDemandeService
{
    /** Types d'acteurs dont le représentant n'est pas une structure : pas de « nom de structure » à saisir. */
    private const TYPES_SANS_STRUCTURE = ['individu', 'groupement non formel', 'producteur individuel'];

    /**
     * @return array{
     *     typesActeurs: Collection, typesAppuis: Collection, localites: Collection, campagnes: Collection,
     *     typesSystemesProductions: Collection, piecesParTypeActeur: Collection, typesActeursMeta: Collection,
     *     appuisDisponibles: Collection
     * }
     */
    public function donnees(): array
    {
        $typesActeurs = TypeActeur::with('typesPieces')->orderBy('lib_type_acteur')->get();
        // « est_equipement » : calculé en une requête pour tous les appuis, pas une par appui.
        $typesAppuis = TypeAppui::with([
            'service',
            'appuis' => fn ($q) => $q->withExists('equipements as est_equipement')->with('uniteDosage'),
        ])->orderBy('lib_type_appui')->get();

        return [
            'typesActeurs' => $typesActeurs,
            'typesAppuis' => $typesAppuis,
            'localites' => Localite::with('departement')->orderBy('lib_localite')->get(),
            'campagnes' => Campagne::ouvertes()->orderByDesc('mois_debut_campagne')->get(),
            'typesSystemesProductions' => TypeSystemeProduction::orderBy('lib_type_systeme_production')->get(),

            // Les pièces requises, par type d'acteur.
            'piecesParTypeActeur' => $typesActeurs->mapWithKeys(fn ($type) => [
                $type->id_type_acteur => $type->typesPieces->map(fn ($piece) => [
                    'id' => $piece->id_type_piece,
                    'libelle' => $piece->lib_type_piece,
                    'obligatoire' => (bool) $piece->pivot->est_obligatoire,
                ])->values(),
            ]),

            // Structure ou individu : décide si l'on demande un nom de structure.
            'typesActeursMeta' => $typesActeurs->mapWithKeys(fn ($type) => [
                $type->id_type_acteur => [
                    'libelle' => $type->lib_type_acteur,
                    'est_structure' => ! in_array(mb_strtolower(trim($type->lib_type_acteur)), self::TYPES_SANS_STRUCTURE, true),
                ],
            ]),

            // Le catalogue des appuis, avec l'unité de mesure.
            'appuisDisponibles' => $typesAppuis->flatMap(fn ($type) => $type->appuis->map(fn ($appui) => [
                'id' => $appui->id_appui,
                'libelle' => $appui->libelle,
                'id_type_appui' => $appui->id_type_appui,
                'unite' => $appui->uniteDosage?->lib_unite ?? '',
                'estEquipement' => (bool) $appui->est_equipement,
            ]))->values(),
        ];
    }
}
