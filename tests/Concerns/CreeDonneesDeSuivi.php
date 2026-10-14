<?php

namespace Tests\Concerns;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Models\SuperficieAttribution;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Demande\Models\SystemeProduction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Agent;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Un jeu de données minimal pour les tests de suivi : les référentiels du
 * DatabaseSeeder, plus une campagne de test couvrant aujourd'hui (elle est donc
 * « en cours » quelle que soit la date d'exécution).
 */
trait CreeDonneesDeSuivi
{
    protected Campagne $campagne;

    protected function preparerDonnees(): void
    {
        // Les observers notifient par base et WebPush : inutile et bruyant ici.
        Notification::fake();

        $this->seed(DatabaseSeeder::class);

        $this->campagne = Campagne::create([
            'lib_campagne' => 'Campagne de test',
            'mois_debut_campagne' => today(),
            'mois_fin_campagne' => today()->addMonth(),
        ]);
    }

    protected function demande(array $attributs = []): Demande
    {
        return Demande::create($attributs + [
            'id_acteur' => Acteur::query()->value('id_acteur'),
            'id_user' => User::role('dp')->value('id'),
            'id_type_acteur' => 1,
            'id_campagne' => $this->campagne->id_campagne,
            'date_demande' => today(),
        ]);
    }

    protected function demandeSoumise(array $attributs = []): Demande
    {
        return $this->demande($attributs + ['date_soummission' => now()->subDays(10)]);
    }

    /** Ajoute une ligne de demande ; $idAppui à null = on ne vise que le type d'appui. */
    protected function ligne(Demande $demande, int $idTypeAppui, ?int $idAppui = null, ?float $quantite = null): LigneDemande
    {
        return LigneDemande::create([
            'id_demande' => $demande->id_demande,
            'id_type_appui' => $idTypeAppui,
            'id_appui' => $idAppui,
            'quantite_demandee' => $quantite,
        ]);
    }

    /** Déclare la superficie d'une demande pour une écologie (bas-fond 1, irrigué 2, plateau 3). */
    protected function systeme(Demande $demande, int $idType, float $superficie): SystemeProduction
    {
        return SystemeProduction::create([
            'id_demande' => $demande->id_demande,
            'id_type_systeme_production' => $idType,
            'superficie' => $superficie,
        ]);
    }

    protected function seance(bool $cloturee = false): SessionCommission
    {
        return SessionCommission::create([
            'date_debut_session' => today()->subDays(5),
            'date_fin_session' => $cloturee ? today()->subDay() : null,
        ]);
    }

    /**
     * Attribue une demande ; $lignes = [id_appui => quantité attribuée].
     * Les dates de création et de retrait se fixent après coup : le modèle les gère seul.
     */
    protected function attribuer(Demande $demande, SessionCommission $session, array $lignes = [], ?string $creeLe = null, ?string $retireLe = null, array $superficies = []): Attribution
    {
        $attribution = Attribution::create([
            'id_demande' => $demande->id_demande,
            'id_session' => $session->id_session,
        ]);

        foreach ($lignes as $idAppui => $quantite) {
            LigneAttribution::create([
                'id_attribution' => $attribution->id_attribution,
                'id_appui' => $idAppui,
                'quantite_attribuee' => $quantite,
            ]);
        }

        // $superficies = [id_type_systeme_production => superficie traitée]
        foreach ($superficies as $idType => $superficie) {
            SuperficieAttribution::create([
                'id_attribution' => $attribution->id_attribution,
                'id_type_systeme_production' => $idType,
                'superficie_traitee' => $superficie,
            ]);
        }

        $dates = array_filter(['created_at' => $creeLe, 'date_destockage' => $retireLe]);
        if ($dates) {
            DB::table('attributions')->where('id_attribution', $attribution->id_attribution)->update($dates);
        }

        return $attribution->refresh();
    }

    /** Publie les résultats d'une attribution (séance supposée close). */
    protected function publier(Attribution $attribution): Attribution
    {
        $attribution->forceFill(['date_publication' => now()])->save();

        return $attribution->refresh();
    }

    /**
     * Fait sortir un équipement comme le ferait un retrait : un bon publié le porte, il passe en
     * service, le stock de l'appui baisse d'une unité et un mouvement de sortie le rattache au bon.
     */
    protected function sortirEquipement(Equipement $equipement): Attribution
    {
        $attribution = $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [$equipement->id_appui => 1]));

        $equipement->update(['en_service' => true]);
        Appui::whereKey($equipement->id_appui)->decrement('quantite_en_stock');

        MouvementStock::create([
            'id_appui' => $equipement->id_appui,
            'id_equipement' => $equipement->id_equipement,
            'id_type_mouvement_stock' => TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE),
            'id_agent' => Agent::value('id_agent'),
            'id_attribution' => $attribution->id_attribution,
            'quantite' => 1,
            'date_mouvement' => now(),
        ]);

        return $attribution;
    }
}
