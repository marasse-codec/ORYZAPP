<?php

namespace App\Http\Controllers\Demande;


use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Utils\Helper;
use Illuminate\Http\Request;

class DemandeRejetController extends Controller
{
    public function __invoke(Request $request) {
    
        $validated = $request->validate([
            'id_demande' => 'required|exists:demandes,id_demande',
            'motifs_rejet' => 'required'
        ]);

        $demande = Demande::where('id_demande', $validated['id_demande'])->firstOrFail();
        $servicesAgent = [auth()->user()->id_service]; // ou un tableau de ses services
        $motif = $validated['motifs_rejet'];

        $demande->rejeter($motif,$servicesAgent);

        return view('demandes.show', ['demande' => $demande, 'layout' => Helper::layout()]);


    }
}