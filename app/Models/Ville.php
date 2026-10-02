<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ville extends Model
{
    /** @use HasFactory<\Database\Factories\VilleFactory> */
    use HasFactory;
    
    public function localites(): HasMany
    {
        return $this->hasMany(
            Localite::class,
            'id_localite',
            'id_localite'
        );
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(
            Demande::class,
            'id_demande',
            'id_demande'
        );
    }
}
