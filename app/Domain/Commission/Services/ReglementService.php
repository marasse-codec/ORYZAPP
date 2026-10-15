<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\Paiement;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Le règlement des attributions. Un acteur règle ce qu'on lui a attribué, en une
 * ou plusieurs fois ; tant qu'il reste quelque chose à payer sur une attribution
 * publiée, il est « en impayé » et ne peut plus déposer de nouvelle demande.
 *
 * Aucun drapeau n'est stocké sur l'acteur : son état se déduit des attributions
 * et des paiements, si bien qu'il se lève de lui-même au dernier règlement.
 *
 * Un règlement est d'abord saisi en brouillon, puis soumis : seuls les règlements
 * soumis comptent dans ce qui est payé.
 */
class ReglementService
{
    /** Les attributions publiées qui ne sont pas soldées, éventuellement pour un seul acteur. */
    public function attributionsImpayees(?int $idActeur = null): Collection
    {
        return Attribution::query()
            ->whereNotNull('date_publication')
            ->when($idActeur, fn ($q) => $q->whereHas('demande', fn ($d) => $d->where('id_acteur', $idActeur)))
            ->with(['demande.acteur', 'lignesAttributions', 'paiements'])
            ->orderBy('date_publication')
            ->get()
            ->filter(fn (Attribution $a) => $a->resteDu() > 0)
            ->values();
    }

    /**
     * Les acteurs en impayé, du plus endetté au moins endetté.
     *
     * @return Collection<int, array{acteur: Acteur, reste_du: float, attributions: Collection<int, Attribution>}>
     */
    public function acteursEnImpaye(): Collection
    {
        return $this->attributionsImpayees()
            ->filter(fn (Attribution $a) => $a->demande?->acteur !== null)
            ->groupBy(fn (Attribution $a) => $a->demande->id_acteur)
            ->map(fn (Collection $attributions) => [
                'acteur' => $attributions->first()->demande->acteur,
                'reste_du' => round($attributions->sum(fn (Attribution $a) => $a->resteDu()), 2),
                'attributions' => $attributions,
            ])
            ->sortByDesc('reste_du')
            ->values();
    }

    public function resteDuActeur(Acteur $acteur): float
    {
        return round($this->attributionsImpayees($acteur->id_acteur)->sum(fn (Attribution $a) => $a->resteDu()), 2);
    }

    public function estEnImpaye(Acteur $acteur): bool
    {
        return $acteur->exists && $this->resteDuActeur($acteur) > 0;
    }

    /** Refuse qu'un acteur en impayé dépose une nouvelle demande, en disant pourquoi. */
    public function verifierActeurLibre(Acteur $acteur): void
    {
        if (! $acteur->exists) {
            return;
        }

        $reste = $this->resteDuActeur($acteur);

        if ($reste > 0) {
            throw new RegleMetierViolee(sprintf(
                "%s n'a pas fini de régler ses attributions précédentes (reste dû : %s F). Une nouvelle demande est impossible tant que le solde n'est pas réglé.",
                $acteur->nom,
                number_format($reste, 0, ',', ' '),
            ));
        }
    }

    /**
     * Saisit un règlement en brouillon. Rien n'est encore considéré comme payé :
     * le comptable peut corriger ou supprimer le brouillon avant de le soumettre.
     *
     * Le montant ne peut dépasser le reste dû, ni porter sur une attribution dont les
     * résultats ne sont pas publiés.
     *
     * @param  array{montant: numeric, date_paiement: string, mode: string, reference?: ?string}  $donnees
     */
    public function creerBrouillon(Attribution $attribution, array $donnees): Paiement
    {
        return DB::transaction(function () use ($attribution, $donnees) {
            $attribution = Attribution::query()->lockForUpdate()->findOrFail($attribution->id_attribution);
            $montant = round((float) $donnees['montant'], 2);
            $this->verifierMontant($attribution, $montant);

            $paiement = $attribution->paiements()->create([
                'montant' => $montant,
                'date_paiement' => $donnees['date_paiement'],
                'mode' => $donnees['mode'],
                'reference' => $donnees['reference'] ?? null,
                'id_user' => Auth::id(),
            ]);

            JournalAction::enregistrer(ActionJournalEnum::paiementBrouillon, $attribution, [
                'paiement' => $paiement->id_paiement,
                'montant' => $montant,
                'mode' => $donnees['mode'],
            ]);

            return $paiement;
        });
    }

    /** Corrige un règlement encore en brouillon ; le journal garde l'avant et l'après. */
    public function modifierBrouillon(Paiement $paiement, array $donnees): Paiement
    {
        return DB::transaction(function () use ($paiement, $donnees) {
            $attribution = Attribution::query()->lockForUpdate()->findOrFail($paiement->id_attribution);
            $paiement = Paiement::query()->lockForUpdate()->findOrFail($paiement->id_paiement);
            $this->exigerBrouillon($paiement);

            $montant = round((float) $donnees['montant'], 2);
            $this->verifierMontant($attribution, $montant);

            $paiement->fill([
                'montant' => $montant,
                'date_paiement' => $donnees['date_paiement'],
                'mode' => $donnees['mode'],
                'reference' => $donnees['reference'] ?? null,
            ]);
            $ecarts = Ecarts::duModele($paiement);
            $paiement->save();

            JournalAction::enregistrer(ActionJournalEnum::paiementBrouillonModifie, $attribution, ['paiement' => $paiement->id_paiement] + $ecarts);

            return $paiement;
        });
    }

    public function supprimerBrouillon(Paiement $paiement): void
    {
        DB::transaction(function () use ($paiement) {
            $paiement = Paiement::query()->lockForUpdate()->findOrFail($paiement->id_paiement);
            $this->exigerBrouillon($paiement);

            JournalAction::enregistrer(ActionJournalEnum::paiementBrouillonSupprime, $paiement->attribution, [
                'paiement' => $paiement->id_paiement,
                'montant' => (float) $paiement->montant,
                'mode' => $paiement->mode,
            ]);

            $paiement->delete();
        });
    }

    /**
     * Soumet un brouillon : le règlement devient définitif et compte dans ce qui est payé.
     * Tout est revérifié sous verrou : deux soumissions simultanées ne peuvent pas dépasser le dû.
     */
    public function soumettre(Paiement $paiement): Paiement
    {
        return DB::transaction(function () use ($paiement) {
            $attribution = Attribution::query()->lockForUpdate()->findOrFail($paiement->id_attribution);
            $paiement = Paiement::query()->lockForUpdate()->findOrFail($paiement->id_paiement);
            $this->exigerBrouillon($paiement);

            $montant = (float) $paiement->montant;
            $this->verifierMontant($attribution, $montant);
            $reste = $attribution->resteDu();

            $paiement->update(['date_soumission' => now(), 'id_user_soumission' => Auth::id()]);

            JournalAction::enregistrer(ActionJournalEnum::paiementEnregistre, $attribution, [
                'paiement' => $paiement->id_paiement,
                'montant' => $montant,
                'mode' => $paiement->mode,
                'reste_du' => round($reste - $montant, 2),
            ]);

            return $paiement;
        });
    }

    /**
     * Les attributions publiées, retrouvées par un terme : « ATT-12 » ou « 12 » (numéro
     * d'attribution), « DEM-7 » (numéro de demande), un numéro de téléphone ou un nom
     * de demandeur. Par défaut, seules celles qui ne sont pas soldées.
     *
     * @return Collection<int, Attribution>
     */
    public function rechercherAttributions(?string $terme, bool $nonSoldeesSeulement = true): Collection
    {
        $terme = trim((string) $terme);

        $attributions = Attribution::query()
            ->whereNotNull('date_publication')
            ->with(['demande.acteur', 'lignesAttributions', 'paiements'])
            ->when($terme !== '', fn ($q) => $this->filtrerParTerme($q, $terme))
            ->orderByDesc('date_publication')
            ->limit(200)
            ->get();

        return $nonSoldeesSeulement
            ? $attributions->filter(fn (Attribution $a) => $a->resteDu() > 0)->values()
            : $attributions;
    }

    /**
     * Comment lire le terme de recherche :
     *  - ATT-12, att12, 12 (moins de 8 chiffres) → numéro d'attribution ;
     *  - DEM-7 → numéro de demande ;
     *  - 8 chiffres ou plus → téléphone du demandeur ;
     *  - sinon → nom du demandeur (ou de sa structure).
     */
    private function filtrerParTerme($requete, string $terme)
    {
        $chiffres = preg_replace('/\D/', '', $terme);

        return match (true) {
            (bool) preg_match('/^(att-?\s*)?\d{1,7}$/i', $terme) => $requete->whereKey((int) $chiffres),
            (bool) preg_match('/^dem-?\s*\d+$/i', $terme) => $requete->where('id_demande', (int) $chiffres),
            strlen($chiffres) >= 8 && strlen($chiffres) === strlen(preg_replace('/[\s.\-+]/', '', $terme))
                => $requete->whereHas('demande.acteur', fn ($a) => $a->where('telephone', 'like', "%{$chiffres}%")),
            default => $requete->whereHas('demande', fn ($d) => $d
                ->where('nom_structure', 'like', "%{$terme}%")
                ->orWhereHas('acteur', fn ($a) => $a->where('nom', 'like', "%{$terme}%"))),
        };
    }

    /** Un montant positif, sur une attribution publiée, qui ne dépasse pas le reste dû. */
    private function verifierMontant(Attribution $attribution, float $montant): void
    {
        if (! $attribution->estPubliee()) {
            throw new RegleMetierViolee("Les résultats de l'attribution n°{$attribution->id_attribution} ne sont pas publiés : rien n'est encore dû.");
        }

        $reste = $attribution->resteDu();

        if ($reste <= 0) {
            throw new RegleMetierViolee("L'attribution n°{$attribution->id_attribution} est déjà soldée.");
        }

        if ($montant <= 0 || $montant > $reste) {
            throw new RegleMetierViolee(sprintf(
                'Le règlement (%s F) doit être positif et ne pas dépasser le reste dû (%s F).',
                number_format($montant, 0, ',', ' '),
                number_format($reste, 0, ',', ' '),
            ));
        }
    }

    /** Un règlement soumis est définitif : on ne le modifie ni ne le supprime. */
    private function exigerBrouillon(Paiement $paiement): void
    {
        if ($paiement->estSoumis()) {
            throw new RegleMetierViolee("Le règlement n°{$paiement->id_paiement} est déjà soumis : il ne peut plus être modifié ni supprimé.");
        }
    }

    /**
     * Les chiffres du tableau de bord : ce qui est dû, encaissé, et qui reste à payer
     * sur les attributions publiées.
     *
     * @return array{du: float, paye: float, reste: float, taux: ?float, nb_attributions: int, nb_brouillons: int, nb_acteurs: int}
     */
    public function synthese(): array
    {
        $publiees = Attribution::query()
            ->whereNotNull('date_publication')
            ->with(['lignesAttributions', 'paiements'])
            ->get();

        $du = round($publiees->sum(fn (Attribution $a) => $a->montantTotal()), 2);
        $paye = round($publiees->sum(fn (Attribution $a) => min($a->totalPaye(), $a->montantTotal())), 2);
        $impayees = $publiees->filter(fn (Attribution $a) => $a->resteDu() > 0);

        return [
            'du' => $du,
            'paye' => $paye,
            'reste' => round($du - $paye, 2),
            'taux' => $du > 0 ? round($paye / $du * 100, 1) : null,
            'nb_attributions' => $impayees->count(),
            'nb_brouillons' => Paiement::query()->brouillons()->count(),
            'nb_acteurs' => $impayees->map(fn (Attribution $a) => $a->demande?->id_acteur)->filter()->unique()->count(),
        ];
    }

    /** Les derniers règlements soumis, du plus récent au plus ancien (page du comptable). */
    public function derniersPaiements(int $limite = 20): Collection
    {
        return Paiement::query()
            ->soumis()
            ->with(['attribution.demande.acteur', 'encaissePar'])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id_paiement')
            ->limit($limite)
            ->get();
    }

    /** Les règlements en brouillon, à vérifier puis soumettre. */
    public function brouillons(): Collection
    {
        return Paiement::query()
            ->brouillons()
            ->with(['attribution.demande.acteur', 'encaissePar'])
            ->orderByDesc('id_paiement')
            ->get();
    }
}
