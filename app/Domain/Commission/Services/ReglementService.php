<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\Paiement;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
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
     * Enregistre un règlement. Il ne peut dépasser ce qui reste dû, ni porter sur une
     * attribution dont les résultats ne sont pas publiés.
     *
     * @param  array{montant: numeric, date_paiement: string, mode: string, reference?: ?string}  $donnees
     */
    public function enregistrerPaiement(Attribution $attribution, array $donnees): Paiement
    {
        return DB::transaction(function () use ($attribution, $donnees) {
            // Verrou : deux encaissements simultanés ne doivent pas dépasser le dû.
            $attribution = Attribution::query()->lockForUpdate()->findOrFail($attribution->id_attribution);

            if (! $attribution->estPubliee()) {
                throw new RegleMetierViolee("Les résultats de l'attribution n°{$attribution->id_attribution} ne sont pas publiés : rien n'est encore dû.");
            }

            $montant = round((float) $donnees['montant'], 2);
            $reste = $attribution->resteDu();

            if ($reste <= 0) {
                throw new RegleMetierViolee("L'attribution n°{$attribution->id_attribution} est déjà soldée.");
            }

            if ($montant > $reste) {
                throw new RegleMetierViolee(sprintf(
                    'Le règlement (%s F) dépasse le reste dû (%s F).',
                    number_format($montant, 0, ',', ' '),
                    number_format($reste, 0, ',', ' '),
                ));
            }

            $paiement = $attribution->paiements()->create([
                'montant' => $montant,
                'date_paiement' => $donnees['date_paiement'],
                'mode' => $donnees['mode'],
                'reference' => $donnees['reference'] ?? null,
                'id_user' => Auth::id(),
            ]);

            JournalAction::enregistrer(ActionJournalEnum::paiementEnregistre, $attribution, [
                'montant' => $montant,
                'mode' => $donnees['mode'],
                'reste_du' => round($reste - $montant, 2),
            ]);

            return $paiement;
        });
    }

    /**
     * Les chiffres du tableau de bord : ce qui est dû, encaissé, et qui reste à payer
     * sur les attributions publiées.
     *
     * @return array{du: float, paye: float, reste: float, taux: ?float, nb_attributions: int, nb_acteurs: int}
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
            'nb_acteurs' => $impayees->map(fn (Attribution $a) => $a->demande?->id_acteur)->filter()->unique()->count(),
        ];
    }

    /** Les derniers règlements encaissés, du plus récent au plus ancien (page du comptable). */
    public function derniersPaiements(int $limite = 20): Collection
    {
        return Paiement::query()
            ->with(['attribution.demande.acteur', 'encaissePar'])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id_paiement')
            ->limit($limite)
            ->get();
    }
}
