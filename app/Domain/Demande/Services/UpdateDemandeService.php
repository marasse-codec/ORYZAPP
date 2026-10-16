<?php

namespace App\Domain\Demande\Services;

use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Demande\Models\Traitement;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\TypeActeur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateDemandeService
{
    public function execute(Demande $demande, array $data, array $files = []): Demande
    {
        // Ni la campagne actuelle de la demande, ni celle vers laquelle on la déplace, ne doivent être clôturées.
        Campagne::exigerOuverteParId($demande->id_campagne, 'modifier une demande');
        Campagne::exigerOuverteParId($data['id_campagne'] ?? null, 'déplacer une demande vers cette campagne');

        return DB::transaction(function () use ($demande, $data, $files) {

            // Le contenu avant modification : le journal dira ce qui a changé.
            $avant = $this->instantane($demande);

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
                'nom_structure'               => TypeActeur::structureRetenue($data['id_type_acteur'] ?? $demande->id_type_acteur, $data['nom_structure'] ?? null),
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

            JournalAction::enregistrer(ActionJournalEnum::demandeModifiee, $demande, Ecarts::entre($avant, $this->instantane($demande->fresh())));

            return $demande;
        });
    }

    /**
     * La demande en texte simple (champs principaux, localités, superficies, appuis demandés),
     * pour comparer avant et après au journal.
     *
     * @return array<string, mixed>
     */
    private function instantane(Demande $demande): array
    {
        $demande->load('localites', 'systemesProductions', 'lignes.typeAppui', 'lignes.appui');

        return [
            'campagne' => $demande->id_campagne,
            'type d\'acteur' => $demande->id_type_acteur,
            'structure' => $demande->nom_structure,
            'localités' => $demande->localites->pluck('lib_localite')->sort()->implode(', '),
            'superficies' => $demande->systemesProductions->sortBy('id_type_systeme_production')
                ->map(fn ($s) => "écologie {$s->id_type_systeme_production} : ".(float) $s->superficie.' ha')->implode(', '),
            'appuis' => $demande->lignes
                ->map(fn ($l) => ($l->appui?->libelle ?? $l->typeAppui?->lib_type_appui).' : '.(float) $l->quantite_demandee)
                ->sort()->implode(', '),
        ];
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
        Traitement::creerTraitements($demande->id_demande, collect($servicesCites));
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