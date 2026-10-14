<?php

namespace App\Http\Controllers\Suivi;

use App\Domain\Suivi\Services\SauvegardeService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

/** Le téléchargement d'une archive : réservé à l'admin (cf. routes). Le chemin vient du service, jamais de la requête. */
class SauvegardeController extends Controller
{
    public function telecharger(string $disque, string $nom, SauvegardeService $sauvegardes)
    {
        $archive = $sauvegardes->trouver($disque, $nom);

        return Storage::disk($archive['disque'])->download($archive['chemin'], $archive['nom']);
    }
}
