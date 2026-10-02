<?php

namespace App\Http\Controllers\Editions;

use App\Enums\RoleEnum;
use App\Enums\StatutAttributionEnum;
use App\Exports\AttributionsExport;
use App\Http\Controllers\Controller;
use App\Models\Attribution;
use App\Models\SessionCommission;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class EditionAttributionController extends Controller
{
    // public function index()
    // {

    //     $attributions = Attribution::query()
    //         ->with(['lignesAttributions.appui', 'demande.acteur', 'session'])
    //         ->orderByDesc('id_attribution')
    //         ->user()
    //         ->get();


    //     $attributions->each(function ($a) {
    //         $a->submissible = $a->id_statut_attribution === StatutAttributionEnum::brouillon;
    //     });

    //     $actions = [];
    //     $session = null ;
    //     if (Auth::user()->hasRole(RoleEnum::agentService->value)) {
    //         $actions = [
    //             'soumettre' => [
    //                 'lien' => 'attributions.soumettre',
    //                 'libelle' => 'Soumettre', // Correction de l'orthographe et ajout d'un libellé
    //             ],
    //             'supprimer' => [
    //                 'lien' => 'attributions.destroy', // Exemple de route de suppression
    //                 'libelle' => 'Supprimer',
    //             ],
    //             'modifier' => [
    //                 'lien' => 'attributions.edit', // Exemple de route de suppression
    //                 'libelle' => 'Modifier',
    //             ]
    //         ];
    //     }

    //     if (Auth::user()->hasRole(RoleEnum::agentCommission->value)) {
    //         $actions['soumettre'] = [
    //             'lien' => 'attributions.valider', // Remplacez par le nom réel de votre route de validation
    //             'libelle' => 'Valider',
    //         ];
    //         $actions['supprimer'] = [
    //             'lien' => 'attributions.annuler', // Remplacez par le nom réel de votre route d'annulation
    //             'libelle' => 'Annuler',
    //         ];

    //         $session = SessionCommission::whereNull('date_fin_session')->first();
            
    //     }

    //     $error = $session ? null : "Aucune session active, veuillez en créer une pour traiter les attributions";


    //     return view('editions.attributions.index', [
    //         'attributions' => $attributions,
    //         'layout' => Helper::layout(),
    //         'sessionOuverte' => $session,
    //         'actions' => $actions,
    //     ])->with('error',$error);
    // }

    
    public function index()
    {

        $attributions = Attribution::query()
            ->with(['lignesAttributions.appui', 'demande.acteur', 'session'])
            ->orderByDesc('id_attribution')
            ->user()
            ->get();


        $attributions->each(function ($a) {
            $a->submissible = $a->id_statut_attribution === StatutAttributionEnum::brouillon;
        });

        $actions = [];
        $session = null ;
        if (Auth::user()->hasRole(RoleEnum::agentService->value)) {
            $actions = [
                'soumettre' => [
                    'lien' => 'attributions.soumettre',
                    'libelle' => 'Soumettre', // Correction de l'orthographe et ajout d'un libellé
                ],
                'supprimer' => [
                    'lien' => 'attributions.destroy', // Exemple de route de suppression
                    'libelle' => 'Supprimer',
                ],
                'modifier' => [
                    'lien' => 'attributions.edit', // Exemple de route de suppression
                    'libelle' => 'Modifier',
                ]
            ];
        }

        if (Auth::user()->hasRole(RoleEnum::agentCommission->value)) {
            $actions['soumettre'] = [
                'lien' => 'attributions.valider', // Remplacez par le nom réel de votre route de validation
                'libelle' => 'Valider',
            ];
            $actions['supprimer'] = [
                'lien' => 'attributions.annuler', // Remplacez par le nom réel de votre route d'annulation
                'libelle' => 'Annuler',
            ];

            $session = SessionCommission::whereNull('date_fin_session')->first();
            
        }

        $error = $session ? null : "Aucune session active, veuillez en créer une pour traiter les attributions";


        return view('editions.attributions.index', [
            'attributions' => $attributions,
            'layout' => Helper::layout(),
            'sessionOuverte' => $session,
            'actions' => $actions,
        ])->with('error',$error);
    }

    public function exportAttributions(Request $request)
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $colonnes = $request->input('colonnes', []);

        $nomFichier = 'Oryza_Attributions_' . now()->format('Y_m_d_His') . '.xlsx';

        // Déclenche la génération et le téléchargement direct
        return Excel::download(new AttributionsExport($dateDebut, $dateFin, $colonnes), $nomFichier);
    }
}