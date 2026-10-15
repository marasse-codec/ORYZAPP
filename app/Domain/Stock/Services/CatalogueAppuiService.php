<?php

namespace App\Domain\Stock\Services;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Models\Appui;
use Illuminate\Support\Facades\DB;

/**
 * Le catalogue des appuis : ce que le magasin peut distribuer. On y décrit un
 * appui (type, unité, dose, coût, seuil d'alerte, systèmes de production) ; on
 * n'y touche JAMAIS au stock : il ne bouge que par les approvisionnements et
 * les retraits, qui laissent des mouvements.
 *
 * Création, modification et suppression sont transactionnelles et journalisées.
 */
class CatalogueAppuiService
{
    /**
     * @param  array{libelle: string, id_type_appui: int|string, id_unite: int|string, dose_ha: float|int|string, cout_unitaire?: mixed, seuil_alerte?: mixed, systemes?: array<int, int|string>}  $donnees
     */
    public function creer(array $donnees): Appui
    {
        return DB::transaction(function () use ($donnees) {
            $appui = Appui::create($this->attributs($donnees) + ['quantite_en_stock' => 0]);
            $appui->typesSystemesProductions()->sync($donnees['systemes'] ?? []);

            JournalAction::enregistrer(ActionJournalEnum::appuiCree, $appui, ['libelle' => $appui->libelle]);

            return $appui;
        });
    }

    public function modifier(Appui $appui, array $donnees): Appui
    {
        return DB::transaction(function () use ($appui, $donnees) {
            // Ce qui change (prix, dose, seuil…), noté avant l'enregistrement : l'historique des prix reste lisible.
            $appui->fill($this->attributs($donnees));
            $ecarts = Ecarts::duModele($appui);
            $appui->save();

            $ecologies = $appui->typesSystemesProductions()->sync($donnees['systemes'] ?? []);

            if ($ecologies['attached'] !== []) {
                $ecarts['écologies ajoutées'] = $ecologies['attached'];
            }

            if ($ecologies['detached'] !== []) {
                $ecarts['écologies retirées'] = $ecologies['detached'];
            }

            JournalAction::enregistrer(ActionJournalEnum::appuiModifie, $appui, ['libelle' => $appui->libelle] + $ecarts);

            return $appui;
        });
    }

    public function supprimer(Appui $appui): void
    {
        DB::transaction(function () use ($appui) {
            $appui = Appui::whereKey($appui->id_appui)->lockForUpdate()->firstOrFail();

            if ($appui->estUtilise()) {
                throw new RegleMetierViolee("« {$appui->libelle} » est utilisé (demandes, attributions ou mouvements de stock) : il ne peut pas être supprimé.");
            }

            // Copie complète de la fiche : elle reste lisible au journal après la suppression.
            JournalAction::enregistrer(ActionJournalEnum::appuiSupprime, $appui, $appui->attributesToArray());

            $appui->typesSystemesProductions()->detach();
            $appui->delete();
        });
    }

    /** Les seuls champs du catalogue : jamais la quantité en stock. */
    private function attributs(array $donnees): array
    {
        return [
            'libelle' => $donnees['libelle'],
            'id_type_appui' => $donnees['id_type_appui'],
            'id_unite' => $donnees['id_unite'],
            'dose_ha' => $donnees['dose_ha'],
            'cout_unitaire' => $donnees['cout_unitaire'] ?? null,
            'seuil_alerte' => $donnees['seuil_alerte'] ?? 0,
        ];
    }
}
