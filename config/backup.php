<?php

use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
 | Sauvegarde ADERIZ (SNF05) : dump de la base + pièces justificatives téléversées.
 | Planifiée dans routes/console.php. Le code source (vendor, .env, etc.) n'est volontairement pas archivé.
 */
return [

    'backup' => [
        'name' => env('APP_NAME', 'aderiz'),

        'source' => [
            'files' => [
                // Pièces des demandes (disque « public »).
                'include' => [storage_path('app/public')],
                'exclude' => [],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ],
            'databases' => [env('DB_CONNECTION', 'mysql')],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            // Disque dédié (voir config/filesystems.php) ; ajouter un disque distant (s3…) pour une copie hors serveur.
            'disks' => explode(',', env('BACKUP_DISKS', 'sauvegardes')),
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // Archive chiffrée si BACKUP_ARCHIVE_PASSWORD est renseigné (les données personnelles des acteurs y figurent).
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
        'verify_backup' => false,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    // Seuls les échecs et les sauvegardes périmées sont notifiés (pas de courriel quotidien de succès).
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],
        'notifiable' => Notifiable::class,
        'mail' => [
            'to' => env('BACKUP_NOTIFY_MAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'ADERIZ'),
            ],
        ],
        'slack' => ['webhook_url' => '', 'channel' => null, 'username' => null, 'icon' => null],
        'discord' => ['webhook_url' => '', 'username' => '', 'avatar_url' => ''],
        'webhook' => ['url' => ''],
    ],

    'log_channel' => null,

    // backup:monitor : alerte si la dernière sauvegarde a plus d'un jour ou si le stockage dépasse 5 Go.
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'aderiz'),
            'disks' => explode(',', env('BACKUP_DISKS', 'sauvegardes')),
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    // backup:clean : rétention 7 j complets, 16 j quotidiens, 8 sem., 4 mois, 2 ans.
    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
