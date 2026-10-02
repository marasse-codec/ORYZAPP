<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable('id_type_mouvement_stock', 'id_agent', 'id_approvisionnement','id_attribution', 'id_appui' , 'quantite','id_equipement','date_mouvement')]
#[Table('mouvements_stocks')]
class MouvementStock extends Model
{
    protected $primaryKey = 'id_mouvement_stock';
    //

    protected $casts = [
        'date_mouvement' => 'datetime'
    ];

    public function approvisionnement()
    {
        return $this->belongsTo(Approvisionnement::class, 'id_approvisionnement', 'id_approvisionnement');
    }

    public function typeMouvement()
    {
        return $this->belongsTo(TypeMouvementStock::class, 'id_type_mouvement_stock', 'id_type_mouvement_stock');
    }

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'id_equipement', 'id_equipement');
    }

    public function appui()
    {
        return $this->belongsTo(Appui::class, 'id_appui', 'id_appui');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function attribution()
    {
        return $this->belongsTo(LigneAttribution::class, 'id_ligne_attribution', 'id_ligne_attribution');
    }
}
