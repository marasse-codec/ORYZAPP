<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable('id_type_mouvement', 'libelle_type_mouvement')]
#[Table('types_mouvements_stocks')]
class TypeMouvementStock extends Model
{
    //

    public function mouvementsStocks()
    {
        return $this->hasMany(MouvementStock::class, 'id_type_mouvement', 'id_type_mouvement');
    }
}
