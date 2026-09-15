<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acteur extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_acteur';

    protected $fillable = ['nom', 'telephone', 'email'];


    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'id_acteur', 'id_acteur');
    }
}
