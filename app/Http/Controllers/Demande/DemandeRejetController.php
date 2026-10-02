<?php

namespace App\Http\Controllers\Demande;


use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Models\SessionCommission;
use App\Models\Traitement;
use App\Utils\Helper;
use Illuminate\Http\Request;

class DemandeRejetController extends Controller
{
    // public function __invoke(Request $request) {
    
    //     $validated = $request->validate([
    //         'id_demande' => 'required|exists:demandes,id_demande',
    //         'motifs_rejet' => 'required'
    //     ]);

    //     /**@var Demande $demande */
    //     $demande = Demande::where('id_demande', $validated['id_demande'])->firstOrFail();
    //     $idServiceAgent = auth()->user()->id_service; // ou un tableau de ses services
    //     $motif = $validated['motifs_rejet'];

    //     /**@var Traitement $traitement */
    //     $traitement = Traitement::where('id_demande',$demande->id_demande)->where('id_service',$idServiceAgent)->first();
        
    //     $traitement->marquerRejet($motif);
    //     $traitement->traitementTerminee();

    //     return view('demandes.show', ['demande' => $demande, 'layout' => Helper::layout()]);


    // }

    public function __invoke(Request $request) {
    
        $validated = $request->validate([
            'id_demande' => 'required|exists:demandes,id_demande',
            'motifs_rejet' => 'required'
        ]);



        // Récupérer la demande en BD

        /**@var Demande $demande */
        $demande = Demande::where('id_demande', $validated['id_demande'])->firstOrFail();
        $motif = $validated['motifs_rejet'];
        $idSession = $request->query('id_session');

        // La décision de rejet se prend en session
        
        $demande->rejeter($idSession,$motif);

        return view('demandes.show', ['demande' => $demande, 'layout' => Helper::layout()]);


    }
}