<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeActeur extends Model
{
    use HasFactory;

    protected $table = 'types_acteurs';

    protected $primaryKey = 'id_type_acteur';

    protected $fillable = ['code_type_acteur', 'lib_type_acteur'];

    /** Les pièces justificatives exigées de ce type d'acteur. */
    public function typesPieces(): BelongsToMany
    {
        return $this->belongsToMany(
            TypePiece::class,
            'types_acteurs_types_pieces',
            'id_type_acteur',
            'id_type_piece'
        )->withPivot('est_obligatoire')->withTimestamps();
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'id_type_acteur', 'id_type_acteur');
    }
}
