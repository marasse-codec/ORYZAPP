<?php

namespace App\Domain\Suivi\Services;

use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Les archives de sauvegarde (spatie/laravel-backup), telles que le stockage les voit :
 * ce que l'admin consulte, télécharge, et la santé de la dernière sauvegarde.
 * La création reste à la commande planifiée (routes/console.php). Aucune archive
 * n'est supprimée automatiquement.
 */
class SauvegardeService
{
    /** Les disques où partent les archives (BACKUP_DISKS, le disque local « sauvegardes » par défaut). */
    public function disques(): array
    {
        return config('backup.backup.destination.disks', ['sauvegardes']);
    }

    /**
     * Toutes les archives, de la plus récente à la plus ancienne.
     *
     * @return Collection<int, array{disque: string, nom: string, chemin: string, taille: int, date: CarbonImmutable}>
     */
    public function archives(): Collection
    {
        $dossier = config('backup.backup.name');

        return collect($this->disques())
            ->flatMap(function (string $disque) use ($dossier) {
                $stockage = Storage::disk($disque);

                return collect($stockage->files($dossier))
                    ->filter(fn (string $chemin) => str_ends_with($chemin, '.zip'))
                    ->map(fn (string $chemin) => [
                        'disque' => $disque,
                        'nom' => basename($chemin),
                        'chemin' => $chemin,
                        'taille' => (int) $stockage->size($chemin),
                        'date' => CarbonImmutable::createFromTimestamp($stockage->lastModified($chemin)),
                    ]);
            })
            ->sortByDesc('date')
            ->values();
    }

    /**
     * La santé des sauvegardes : la dernière doit dater de moins de $joursMax jours
     * (le même seuil que backup:monitor).
     *
     * @return array{derniere: ?CarbonImmutable, age_jours: ?int, saine: bool, nombre: int, taille: int}
     */
    public function etat(): array
    {
        $archives = $this->archives();
        $derniere = $archives->first()['date'] ?? null;
        $joursMax = (int) (config('backup.monitor_backups.0.health_checks')[\Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class] ?? 1);

        return [
            'derniere' => $derniere,
            'age_jours' => $derniere ? (int) $derniere->diffInDays(now()) : null,
            'saine' => $derniere !== null && $derniere->greaterThan(now()->subDays($joursMax)),
            'nombre' => $archives->count(),
            'taille' => (int) $archives->sum('taille'),
        ];
    }

    /**
     * Retrouve une archive par son disque et son nom : jamais par un chemin fourni
     * tel quel, pour qu'on ne puisse sortir du dossier des sauvegardes.
     */
    public function trouver(string $disque, string $nom): array
    {
        $archive = $this->archives()->first(fn (array $a) => $a['disque'] === $disque && $a['nom'] === $nom);

        if ($archive === null) {
            throw new RegleMetierViolee("L'archive « {$nom} » est introuvable.");
        }

        return $archive;
    }
}
