<?php

namespace App\Domain\Demande\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sauvegarde automatique d'un formulaire en cours de saisie (hors fichiers téléversés). */
class BrouillonSaisie extends Model
{
    public const TYPE_DEMANDE = 'demande';

    protected $table = 'brouillons_saisies';

    protected $primaryKey = 'id_brouillon';

    protected $fillable = ['id_user', 'type', 'donnees'];

    protected function casts(): array
    {
        return ['donnees' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
