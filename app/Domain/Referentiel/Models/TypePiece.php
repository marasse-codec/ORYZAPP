<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Demande\Models\PieceDemande;
use App\Domain\Shared\Contracts\EstUtilisable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypePiece extends Model implements EstUtilisable
{
    protected $table = 'types_pieces';

    protected $primaryKey = 'id_type_piece';

    protected $fillable = ['lib_type_piece'];

    public function piecesDemandes(): HasMany
    {
        return $this->hasMany(PieceDemande::class, 'id_type_piece', 'id_type_piece');
    }

    public function typesActeurs(): BelongsToMany
    {
        return $this->belongsToMany(
            TypeActeur::class,
            'types_acteurs_types_pieces',
            'id_type_piece',
            'id_type_acteur'
        )->withPivot('est_obligatoire')->withTimestamps();
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->piecesDemandes()->exists();
    }
}
