<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approvisionnement extends Model
{
    protected $primaryKey = 'id_approvisionnement';

    protected $fillable = ['date_approvisionnement', 'reference_commande', 'origine'];

    protected $casts = ['date_approvisionnement' => 'date'];

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class, 'id_approvisionnement', 'id_approvisionnement');
    }
}
