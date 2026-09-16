<?php

namespace App\Services;

use App\Enums\StatutDemandeE;
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
     * Enregistre une demande et tout ce qui la constitue. Le tout en une
     * transaction : une demande sans ses lignes ni ses superficies serait
     * inexploitable en instruction.
     *
     * @param  array<string, mixed>  $donnees  Données validées du formulaire.
     * @param  array<int, UploadedFile|null>  $pieces  Fichiers indexés par type de pièce.
     */
    public function creer(array $donnees, array $pieces = []): Demande
    {
        return DB::transaction(function () use ($donnees, $pieces) {
            $acteur = $this->resoudreDemandeur($donnees);

            $demande = Demande::create([
                'id_acteur' => $acteur->id_acteur,
                'id_type_acteur' => $donnees['id_type_acteur'],
                'id_localite' => $donnees['id_localite'],
                'date_demande' => now()->toDateString(),

                'periode_debut_mise_en_place' => $donnees['periode_debut_mise_en_place'],
                'periode_fin_mise_en_place' => $donnees['periode_fin_mise_en_place'],

                'soumise' => false, // Par défaut à false en bd

                'id_user' => Auth::user()->id,
                'id_campagne' => $donnees['id_campagne']
            ]);

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
        $acteur = Acteur::firstOrNew(['telephone' => $donnees['telephone']]);

        $acteur->nom = $donnees['nom'];
        $acteur->email = $donnees['email'] ?? $acteur->email;
        $acteur->save();

        return $acteur;
    }

    /** Seules les surfaces réellement exploitées sont enregistrées. */
    private function enregistrerSuperficies(Demande $demande, array $superficies): void
    {
        foreach ($superficies as $idTypeSystemeProduction => $superficie) {
            if ((float) $superficie <= 0) {
                continue;
            }

            SystemeProduction::create([
                'id_demande' => $demande->id_demande,
                'id_type_systeme_production' => $idTypeSystemeProduction,
                'superficie' => (float) $superficie,
            ]);
        }
    }

    private function enregistrerLignes(Demande $demande, array $lignes): void
    {
        $servicesCites = [];

        foreach ($lignes as $ligne) {
            $ligneD = LigneDemande::create([
                'id_demande' => $demande->id_demande,
                'id_type_appui' => $ligne['id_type_appui'],
                'quantite_demandee' => $ligne['quantite_demandee'],
            ]);

            // Préchargement de la relation
            $ligneD->load('typeAppui.service');

            // Récupération des services concernnées
            if ($ligneD->typeAppui && $ligneD->typeAppui->service) {
                $service = $ligneD->typeAppui->service;
                $servicesCites[$service->id_service] = $service;
            }
        }

        // Unifications des services
        $servicesUniques = array_values($servicesCites);

        // Création de traitement pour chaque service (imputation)
        foreach ($servicesUniques as $service) {
            Traitement::create([
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
                'id_demande' => $demande->id_demande,
                'id_type_piece' => $idTypePiece,
                'chemin_fichier' => $fichier->store("demandes/{$demande->id_demande}", 'public'),
            ]);
        }
    }
}
