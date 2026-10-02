<?php

namespace App\Services;

use App\Models\Acteur;
use App\Models\Demande;
use App\Models\LigneDemande;
use App\Models\PieceDemande;
use App\Models\SystemeProduction;
use App\Models\Traitement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreationDemandeService
{
    /**
     * Enregistre une demande et tout ce qui la constitue en une seule transaction.
     *
     * @param  array<string, mixed>  $donnees  Données validées du formulaire.
     * @param  array<int, UploadedFile|null>  $pieces  Fichiers indexés par type de pièce.
     */
    public function creer(array $donnees, array $pieces = []): Demande
    {
        // Vérifier si l'acteur a une demande en cours
        $acteur = $this->resoudreDemandeur($donnees);

        return DB::transaction(function () use ($donnees, $pieces) {
            $acteur = $this->resoudreDemandeur($donnees);

            $demande = Demande::create([
                'id_acteur'                   => $acteur->id_acteur,
                'id_type_acteur'              => $donnees['id_type_acteur'],
                'nom_structure'               => $donnees['nom_structure'] ?? null,
                'date_demande'                => now()->toDateString(),

                'periode_debut_mise_en_place' => $donnees['periode_debut_mise_en_place'],
                'periode_fin_mise_en_place'   => $donnees['periode_fin_mise_en_place'],

                'date_soummission'            => null, // Brouillon par défaut à la création

                'id_user'                     => Auth::id(),
                'id_campagne'                 => $donnees['id_campagne'],
            ]);

            $this->enregistrerLocalites($demande, $donnees['localites'] ?? []);
            $this->enregistrerSuperficies($demande, $donnees['superficies'] ?? []);
            $this->enregistrerLignes($demande, $donnees['lignes'] ?? []);
            $this->enregistrerPieces($demande, $pieces);

            return $demande;
        });
    }

    /**
     * Le téléphone identifie le demandeur : un acteur déjà connu est réutilisé
     * plutôt que dupliqué, et ses coordonnées sont rafraîchies au passage.
     */
    private function resoudreDemandeur(array $donnees): Acteur
    {
        // On recherche par nom et date de naissance exacts, ou on crée un nouveau profil
        $acteur = Acteur::firstOrNew([
            'nom' => $donnees['nom'],
            'date_naissance' => $donnees['date_naissance'],
        ]);

        $acteur->telephone = $donnees['telephone'];
        $acteur->email = $donnees['email'] ?? $acteur->email;
        $acteur->save();

        return $acteur;
    }

    /** Attachement des localités via la relation Many-to-Many. */
    private function enregistrerLocalites(Demande $demande, array $localites): void
    {
        if (!empty($localites)) {
            $demande->localites()->attach($localites);
        }
    }

    /** Seules les surfaces réellement exploitées (> 0) sont enregistrées. */
    private function enregistrerSuperficies(Demande $demande, array $superficies): void
    {
        foreach ($superficies as $idTypeSystemeProduction => $superficie) {
            if ((float) $superficie <= 0) {
                continue;
            }

            SystemeProduction::create([
                'id_demande'                  => $demande->id_demande,
                'id_type_systeme_production' => $idTypeSystemeProduction,
                'superficie'                  => (float) $superficie,
            ]);
        }
    }

   private function enregistrerLignes(Demande $demande, array $lignes): void
    {
        $servicesCites = [];

        // Optionnel : si on est en mise à jour, on supprime les anciennes lignes
        // $demande->lignes()->delete();

        foreach ($lignes as $ligne) {
            // Ignorer si aucun type d'appui n'est sélectionné
            if (empty($ligne['id_type_appui'])) {
                continue;
            }

            // Nettoyage de l'appui spécifique s'il est vide
            $idAppui = !empty($ligne['id_appui']) ? $ligne['id_appui'] : null;

            // Formater la quantité demandée
            $quantite = isset($ligne['quantite_demandee']) && $ligne['quantite_demandee'] !== '' 
                ? (float) $ligne['quantite_demandee'] 
                : null;

            $ligneD = LigneDemande::create([
                'id_demande'        => $demande->id_demande,
                'id_type_appui'     => $ligne['id_type_appui'],
                'id_appui'          => $idAppui,
                'quantite_demandee' => $quantite,
            ]);

            // Charger le service associé au type d'appui
            $ligneD->load('typeAppui.service');

            if ($ligneD->typeAppui && $ligneD->typeAppui->service) {
                $service = $ligneD->typeAppui->service;
                $servicesCites[$service->id_service] = $service;
            }
        }

        // Création des traitements pour chaque service concerné (évite les doublons en BDD)
        foreach ($servicesCites as $service) {
            Traitement::firstOrCreate([
                'id_demande' => $demande->id_demande,
                'id_service' => $service->id_service,
            ]);
        }
    }

    /** @param array<int, UploadedFile|null> $pieces */
    private function enregistrerPieces(Demande $demande, array $pieces): void
    {
        foreach ($pieces as $idTypePiece => $fichier) {
            if (! $fichier instanceof UploadedFile) {
                continue;
            }

            PieceDemande::create([
                'id_demande'     => $demande->id_demande,
                'id_type_piece'  => $idTypePiece,
                'chemin_fichier' => $fichier->store("demandes/{$demande->id_demande}", 'public'),
            ]);
        }
    }
}