<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

#[Signature('app:generate-filament-ressources')]
#[Description('Command description')]
class GenerateFilamentRessources extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Tableau des modèles à ignorer
        $excludedModels = [
            'User',
            'ResourceSubscription',
            'UniteAppui',
            'VerificationService',
            'LocationAppui'
        ];

        $files = File::files(app_path('Models'));

        foreach ($files as $file) {
            $modelName = $file->getFilenameWithoutExtension();

            // 1. Ignorer si présent dans la liste d'exclusion
            if (in_array($modelName, $excludedModels, true)) {
                $this->warn("⏭️ Ignoré (liste d'exclusion) : {$modelName}");
                continue;
            }

            // 2. Ignorer si le fichier ou dossier de Ressource Filament existe déjà
            $resourceFile = app_path("Filament/Admin/Resources/{$modelName}Resource.php");
            $resourceDir = app_path("Filament/Admin/Resources/{$modelName}Resource");

            if (File::exists($resourceFile) || File::isDirectory($resourceDir)) {
                $this->warn("⏭️ Ignoré (ressource déjà existante) : {$modelName}");
                continue;
            }

            $this->info("⚙️ Génération pour : {$modelName}");

            Artisan::call('filament:resource', [
                'name' => $modelName,
                '--generate' => true,
            ]);

            $this->line(Artisan::output());
        }

        $this->info('✅ Génération terminée avec succès !');
    }
}
