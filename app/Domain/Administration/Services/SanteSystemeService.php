<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Support\Verification;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Suivi\Services\SauvegardeService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Les contrôles de l'écran « Santé du système » : est-ce que la base répond, les
 * sauvegardes sont à jour, les tâches automatiques tournent, le disque n'est pas
 * plein… Chaque contrôle est une petite méthode qui rend une Verification.
 *
 * Un contrôle qui plante ne fait pas planter l'écran : il s'affiche « Problème ».
 */
class SanteSystemeService
{
    /** Clé du cache où le planificateur note son dernier passage (cf. routes/console.php). */
    public const CLE_PLANIFICATEUR = 'sante.planificateur.dernier_passage';

    public function __construct(private readonly SauvegardeService $sauvegardes) {}

    /** @return array<int, Verification> */
    public function verifier(): array
    {
        $controles = [
            'Base de données' => fn () => $this->baseDeDonnees(),
            'Sauvegardes' => fn () => $this->sauvegarde(),
            'Tâches automatiques' => fn () => $this->planificateur(),
            "File d'attente" => fn () => $this->fileAttente(),
            'Espace disque' => fn () => $this->espaceDisque(),
            'Mode débogage' => fn () => $this->modeDebogage(),
            'Connexions refusées (24 h)' => fn () => $this->connexionsRefusees(),
            'Comptes' => fn () => $this->comptes(),
            'Versions' => fn () => $this->versions(),
        ];

        return collect($controles)
            ->map(function (callable $controle, string $libelle) {
                try {
                    return $controle();
                } catch (Throwable $e) {
                    return new Verification($libelle, Verification::ERREUR, 'Contrôle impossible : '.$e->getMessage());
                }
            })
            ->values()
            ->all();
    }

    private function baseDeDonnees(): Verification
    {
        $debut = microtime(true);
        DB::select('SELECT 1');
        $ms = (int) round((microtime(true) - $debut) * 1000);

        return new Verification(
            'Base de données',
            $ms < 500 ? Verification::OK : Verification::ALERTE,
            "Elle répond en {$ms} ms (".DB::connection()->getDriverName().').',
        );
    }

    private function sauvegarde(): Verification
    {
        $etat = $this->sauvegardes->etat();

        if ($etat['derniere'] === null) {
            return new Verification('Sauvegardes', Verification::ERREUR, 'Aucune sauvegarde trouvée.');
        }

        return new Verification(
            'Sauvegardes',
            $etat['saine'] ? Verification::OK : Verification::ALERTE,
            'Dernière le '.$etat['derniere']->format('d/m/Y à H:i')." ({$etat['nombre']} archive(s) conservée(s)).",
        );
    }

    /** Le planificateur note son passage chaque minute ; s'il ne l'a pas fait depuis longtemps, il est arrêté. */
    private function planificateur(): Verification
    {
        $dernier = Cache::get(self::CLE_PLANIFICATEUR);

        if ($dernier === null) {
            return new Verification('Tâches automatiques', Verification::ALERTE, 'Aucun passage enregistré : les rappels et sauvegardes automatiques ne tournent peut-être pas.');
        }

        $dernier = Carbon::parse($dernier);
        $minutes = (int) $dernier->diffInMinutes(now());

        return new Verification(
            'Tâches automatiques',
            $minutes <= 10 ? Verification::OK : Verification::ALERTE,
            'Dernier passage le '.$dernier->format('d/m/Y à H:i')." (il y a {$minutes} min).",
        );
    }

    private function fileAttente(): Verification
    {
        $enAttente = DB::table('jobs')->count();
        $enEchec = DB::table('failed_jobs')->count();

        return new Verification(
            "File d'attente",
            $enEchec > 0 ? Verification::ALERTE : Verification::OK,
            "{$enAttente} tâche(s) en attente, {$enEchec} en échec (notifications, e-mails…).",
        );
    }

    private function espaceDisque(): Verification
    {
        $libre = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if (! $libre || ! $total) {
            return new Verification('Espace disque', Verification::INFO, "L'hébergeur ne donne pas cette information.");
        }

        $pourcentLibre = round($libre / $total * 100);

        return new Verification(
            'Espace disque',
            match (true) {
                $pourcentLibre < 5 => Verification::ERREUR,
                $pourcentLibre < 15 => Verification::ALERTE,
                default => Verification::OK,
            },
            number_format($libre / 1073741824, 1, ',', ' ').' Go libres sur '.number_format($total / 1073741824, 1, ',', ' ')." Go ({$pourcentLibre} %).",
        );
    }

    /** En production, le mode débogage affiche des détails techniques aux visiteurs : à éviter. */
    private function modeDebogage(): Verification
    {
        $risque = config('app.debug') && app()->environment('production');

        return new Verification(
            'Mode débogage',
            $risque ? Verification::ERREUR : Verification::OK,
            $risque ? 'Activé en production : les erreurs détaillées sont visibles par tous.' : (config('app.debug') ? 'Activé (environnement de développement).' : 'Désactivé.'),
        );
    }

    private function connexionsRefusees(): Verification
    {
        $nombre = JournalAction::where('action', ActionJournalEnum::connexionEchouee->value)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return new Verification(
            'Connexions refusées (24 h)',
            $nombre >= 20 ? Verification::ALERTE : Verification::OK,
            "{$nombre} tentative(s) refusée(s). Le détail est dans le journal des actions.",
        );
    }

    private function comptes(): Verification
    {
        $actifs = User::actifs()->count();
        $desactives = User::where('actif', false)->count();

        return new Verification('Comptes', Verification::INFO, "{$actifs} compte(s) actif(s), {$desactives} désactivé(s).");
    }

    private function versions(): Verification
    {
        return new Verification('Versions', Verification::INFO, 'PHP '.PHP_VERSION.', Laravel '.app()->version().'.');
    }
}
