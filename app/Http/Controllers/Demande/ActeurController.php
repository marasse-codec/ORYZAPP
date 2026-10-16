<?php

namespace App\Http\Controllers\Demande;

use App\Domain\Commission\Services\ReglementService;
use App\Domain\Demande\Models\Demande;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActeurController extends Controller
{
    /** Autocomplétion de la saisie d'une demande : retrouve un acteur déjà connu par son nom et sa date de naissance. */
    public function rechercherParNomEtDate(Request $request, ReglementService $reglements)
    {
        $nom = $request->query('nom');
        $dateNaissance = $request->query('date_naissance');

        // On exige au moins un nom d'une certaine longueur et une date de naissance valide
        if (empty($nom) || strlen(trim($nom)) < 2 || empty($dateNaissance)) {
            return response()->json([]);
        }

        $demandes = Demande::with(['acteur', 'typeActeur'])
            ->whereHas('acteur', function ($query) use ($nom, $dateNaissance) {
                $query->whereLike('nom', '%' . $nom . '%')
                    ->whereDate('date_naissance', $dateNaissance); // Filtrage strict sur la date de naissance (au jour, quel que soit le format de stockage)
            })
            ->latest('date_demande')
            ->limit(10)
            ->get();
        
        $resultats = $demandes->map(function ($demande) use ($reglements) {
            return [
                'id_acteur' => $demande->acteur->id_acteur ?? null,
                'nom' => $demande->acteur->nom ?? '',
                'date_naissance' => $demande->acteur->date_naissance?->format('Y-m-d') ?? '',
                'telephone' => $demande->acteur->telephone ?? '',
                'email' => $demande->acteur->email ?? '',
                // Pour information seulement : le type d'acteur et la structure se choisissent à chaque demande.
                'precedente' => trim(($demande->typeActeur?->lib_type_acteur ?? '').($demande->nom_structure ? ' — '.$demande->nom_structure : '')),
                // Le formulaire peut ainsi prévenir avant la saisie : l'enregistrement sera refusé.
                'reste_du' => $demande->acteur ? $reglements->resteDuActeur($demande->acteur) : 0,
            ];
        })->unique('id_acteur')->values();

        return response()->json($resultats);
    }

}
