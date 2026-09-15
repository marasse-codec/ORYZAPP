<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Approvisionnement extends Model
{
    protected $primaryKey = 'id_approvisionnement';

    protected $fillable = ['date_approvisionnement', 'reference_commande', 'origine'];

    protected $casts = ['date_approvisionnement' => 'date'];

    public function mouvement(): HasOne
    {
        return $this->hasOne(MouvementStock::class, 'id_approvisionnement', 'id_approvisionnement');
    }
}
