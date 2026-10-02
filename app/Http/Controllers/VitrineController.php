<?php

namespace App\Http\Controllers;

use App\Models\Appui;
use App\Models\TypeActeur;
use App\Models\TypeAppui;
use App\Models\TypeSystemeProduction;
use Illuminate\Contracts\View\View;

/**
 * La page publique. Elle explique aux acteurs de la filière comment déposer
 * une demande et ce qu'ils peuvent solliciter.
 *
 * Rien de ce qu'elle affiche ne touche aux stocks : les quantités disponibles
 * ne regardent que la commission, qui arbitre, et le magasin, qui sert.
 */
class VitrineController extends Controller
{
    public function __invoke(): View
    {
        $appuis = Appui::with('typeAppui.service')
            ->orderBy('libelle')
            ->get();

        return view('vitrine', [
            'typesActeurs' => TypeActeur::with('typesPieces')->orderBy('lib_type_acteur')->get(),
            'typesSystemesProduction' => TypeSystemeProduction::orderBy('lib_type_systeme_production')->get(),
            'appuisParType' => $appuis->groupBy(fn (Appui $appui) => $appui->typeAppui?->lib_type_appui ?? 'Autres'),

            // Repères chiffrés de l'accroche, lus sur le référentiel réel
            // plutôt qu'écrits en dur : ils suivent le catalogue.
            'nbTypesAppuis' => TypeAppui::where('est_intrant', false)->count(),
        ]);
    }
}
