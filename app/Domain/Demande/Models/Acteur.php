<?php

namespace App\Domain\Demande\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Domain\Commission\Models\Attribution;
use Illuminate\Notifications\Notifiable;

class Acteur extends Model
{
    use HasFactory;
    use Notifiable;

    protected $primaryKey = 'id_acteur';

    protected $fillable = ['nom', 'telephone', 'date_naissance', 'email'];

    protected $casts = [
        'date_naissance' => 'date'
    ];

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'id_acteur', 'id_acteur');
    }

    /** Les attributions reçues par l'acteur, au fil de ses demandes. */
    public function attributions(): HasManyThrough
    {
        return $this->hasManyThrough(Attribution::class, Demande::class, 'id_acteur', 'id_demande', 'id_acteur', 'id_demande');
    }
}
