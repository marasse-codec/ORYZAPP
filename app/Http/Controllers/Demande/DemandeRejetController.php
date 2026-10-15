<?php

namespace App\Http\Controllers\Demande;


use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\Traitement;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        Campagne::exigerOuverteParId($demande->id_campagne, 'rejeter une demande');

        // La décision de rejet se prend en session
        
        DB::transaction(function () use ($demande, $idSession, $motif) {
            $demande->rejeter($idSession, $motif);

            JournalAction::enregistrer(ActionJournalEnum::demandeRejetee, $demande, ['motif' => $motif, 'id_session' => $idSession]);
        });

        return view('demandes.show', ['demande' => $demande, 'layout' => Helper::layout()]);


    }
}