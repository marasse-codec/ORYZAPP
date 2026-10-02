<?php

namespace App\Http\Controllers;

use App\Models\Acteur;
use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActeurController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
        ]);

        $acteur = Acteur::create([
            'nom' => $request->nom,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'user_id' => Auth::id(), // Sécurité : on utilise l'ID de la session
            'id_type_acteur' => 1,
        ]);

        return response()->json($acteur, 201);
    }

    // 1. Nouvelle méthode de recherche pour l'autocomplétion

    public function rechercherParNomEtDate(Request $request)
    {
        $nom = $request->query('nom');
        $dateNaissance = $request->query('date_naissance');

        // On exige au moins un nom d'une certaine longueur et une date de naissance valide
        if (empty($nom) || strlen(trim($nom)) < 2 || empty($dateNaissance)) {
            return response()->json([]);
        }

        $demandes = Demande::with(['acteur', 'typeActeur'])
            ->whereHas('acteur', function ($query) use ($nom, $dateNaissance) {
                $query->where('nom', 'like', '%' . $nom . '%')
                    ->where('date_naissance', $dateNaissance); // Filtrage strict sur la date de naissance
            })
            ->latest('date_demande')
            ->limit(10)
            ->get();
        
        $resultats = $demandes->map(function ($demande) {
            return [
                'id_acteur' => $demande->acteur->id_acteur ?? null,
                'nom' => $demande->acteur->nom ?? '',
                'date_naissance' => $demande->acteur->date_naissance?->format('Y-m-d') ?? '',
                'telephone' => $demande->acteur->telephone ?? '',
                'email' => $demande->acteur->email ?? '',
                'nom_structure' => $demande->nom_structure ?? '',
                'id_type_acteur' => $demande->id_type_acteur ?? '',
            ];
        })->unique('id_acteur')->values();

        return response()->json($resultats);
    }

}
