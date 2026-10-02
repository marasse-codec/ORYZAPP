<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\LigneDemande;
use App\Models\Traitement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateDemandeService
{
    public function execute(Demande $demande, array $data, array $files = []): Demande
    {
        return DB::transaction(function () use ($demande, $data, $files) {

            // 1. Mise à jour de l'acteur associé
            if ($demande->acteur) {
                $demande->acteur->update([
                    'id_type_acteur' => $data['id_type_acteur'] ?? $demande->acteur->id_type_acteur,
                    'nom'            => $data['nom'] ?? $demande->acteur->nom,
                    'date_naissance' => $data['date_naissance'] ?? $demande->acteur->date_naissance,
                    'telephone'      => $data['telephone'] ?? $demande->acteur->telephone,
                    'email'          => $data['email'] ?? null,
                    'nom_structure'  => $data['nom_structure'] ?? null,
                ]);
            }

            // 2. Mise à jour de la demande principale
            $demande->update([
                'id_type_acteur'              => $data['id_type_acteur'] ?? $demande->id_type_acteur,
                'id_campagne'                 => $data['id_campagne'] ?? $demande->id_campagne,
                'nom_structure'               => $data['nom_structure'] ?? null,
                'commentaire_rejet'           => null, // Réinitialisation de l'observation de renvoi
                'date_rejet'                  => null,
            ]);

            // 3. Mise à jour des localités (relation Pivot / BelongsToMany)
            if (isset($data['localites'])) {
                $demande->localites()->sync($data['localites']);
            }

            // 4. Mise à jour des superficies (Systèmes de production)
            $this->enregistrerSuperficies($demande, $data['superficies'] ?? []);

            // 5. Mise à jour des lignes d'appuis & synchronisation des traitements
            $this->enregistrerLignes($demande, $data['lignes'] ?? []);

            // 6. Gestion des pièces jointes
            if (!empty($files)) {
                $this->enregistrerPieces($demande, $files);
            }

            return $demande;
        });
    }

    private function enregistrerSuperficies(Demande $demande, array $superficies): void
    {
        $demande->systemesProductions()->delete();

        foreach ($superficies as $idTypeSysteme => $superficie) {
            if (!is_null($superficie) && $superficie !== '' && (float)$superficie > 0) {
                $demande->systemesProductions()->create([
                    'id_type_systeme_production' => $idTypeSysteme,
                    'superficie'                  => $superficie,
                ]);
            }
        }
    }

    private function enregistrerLignes(Demande $demande, array $lignes): void
    {
        // Nettoyage des anciennes lignes
        $demande->lignes()->delete();

        $servicesCites = [];

        foreach ($lignes as $ligne) {
            if (empty($ligne['id_type_appui'])) {
                continue;
            }

            $idAppui = !empty($ligne['id_appui']) ? $ligne['id_appui'] : null;
            $quantite = isset($ligne['quantite_demandee']) && $ligne['quantite_demandee'] !== '' 
                ? (float) $ligne['quantite_demandee'] 
                : null;

            $ligneD = LigneDemande::create([
                'id_demande'        => $demande->id_demande,
                'id_type_appui'     => $ligne['id_type_appui'],
                'id_appui'          => $idAppui,
                'quantite_demandee' => $quantite,
            ]);

            $ligneD->load('typeAppui.service');

            if ($ligneD->typeAppui && $ligneD->typeAppui->service) {
                $service = $ligneD->typeAppui->service;
                $servicesCites[$service->id_service] = $service;
            }
        }

        // Garantir qu'une instance de traitement existe pour chaque service concerné
        foreach ($servicesCites as $service) {
            Traitement::firstOrCreate([
                'id_demande' => $demande->id_demande,
                'id_service' => $service->id_service,
            ]);
        }
    }

    private function enregistrerPieces(Demande $demande, array $files): void
    {
        foreach ($files as $idTypePiece => $file) {
            if ($file && $file->isValid()) {
                // Récupération de l'ancienne pièce pour supprimer le fichier physique
                $pieceExistante = $demande->piecesDemandes()
                    ->where('id_type_piece', $idTypePiece)
                    ->first();

                if ($pieceExistante && $pieceExistante->chemin_fichier) {
                    Storage::disk('public')->delete($pieceExistante->chemin_fichier);
                }

                $path = $file->store('pieces_demandes', 'public');

                $demande->piecesDemandes()->updateOrCreate(
                    ['id_type_piece' => $idTypePiece],
                    ['chemin_fichier' => $path]
                );
            }
        }
    }
}