<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
