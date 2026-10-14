<?php

namespace App\Domain\Demande\Services;

use App\Domain\Demande\Models\Demande;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Crée une série de demandes qui partagent campagne, localités, période, type
 * d'acteur et appuis demandés, chaque acteur n'apportant que son identité et
 * ses quantités.
 *
 * Tout ou rien : une seule transaction pour la série. Si une ligne échoue, ni
 * demande, ni demandeur, ni trace de journal ne subsiste pour les autres.
 */
class CreationDemandeSerieService
{
    public function __construct(
        private CreationDemandeService $creation,
        private SoumissionDemandeService $soumission,
        private VisiteDemandeService $visite,
    ) {}

    /**
     * @param  array<string, mixed>  $commun  campagne, type d'acteur, localités, période, appuis (liste de [id_type_appui, id_appui])
     * @param  array<int, array<string, mixed>>  $acteurs  nom, date_naissance, telephone, nom_structure, quantites (par appui, dans l'ordre), visite (observations de la visite de terrain, facultatives sauf pour soumettre)
     * @return Collection<int, Demande>
     */
    public function creer(array $commun, array $acteurs, bool $soumettre = false): Collection
    {
        return DB::transaction(function () use ($commun, $acteurs, $soumettre) {
            $demandes = collect();

            foreach ($acteurs as $acteur) {
                $demande = $this->creation->creer([
                    'id_type_acteur' => $commun['id_type_acteur'],
                    'id_campagne' => $commun['id_campagne'],
                    'localites' => $commun['localites'],
                    'periode_debut_mise_en_place' => $commun['periode_debut_mise_en_place'],
                    'periode_fin_mise_en_place' => $commun['periode_fin_mise_en_place'],
                    'nom' => $acteur['nom'],
                    'date_naissance' => $acteur['date_naissance'],
                    'telephone' => $acteur['telephone'],
                    'nom_structure' => $acteur['nom_structure'] ?? null,
                    'lignes' => $this->lignes($commun['appuis'], $acteur['quantites'] ?? []),
                ]);

                if (filled($acteur['visite'] ?? null)) {
                    $this->visite->enregistrer($demande, $acteur['visite']);
                }

                // Sans visite enregistrée, le service de soumission refuse : tout le lot est alors annulé.
                if ($soumettre) {
                    $this->soumission->soumettre($demande);
                }

                $demandes->push($demande);
            }

            return $demandes;
        });
    }

    /** Une ligne de demande par appui commun ; la quantité, facultative, est celle de cet acteur. */
    private function lignes(array $appuis, array $quantites): array
    {
        return collect($appuis)->values()->map(fn (array $appui, int $k) => [
            'id_type_appui' => $appui['id_type_appui'],
            'id_appui' => $appui['id_appui'] ?? null,
            'quantite_demandee' => $quantites[$k] ?? null,
        ])->all();
    }
}
