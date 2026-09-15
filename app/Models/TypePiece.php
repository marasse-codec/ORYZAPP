<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypePiece extends Model
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
}
