<?php

namespace App\Domain\Stock\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('matricule_equipement', 'etat', 'en_service', 'id_appui')]
#[Table('equipements')]
class Equipement extends Model
{

    protected $primaryKey = 'id_equipement';

    protected $casts = [
        'en_service' => 'boolean',
    ];

    public function mouvementsStocks()
    {
        return $this->hasMany(MouvementStock::class, 'id_equipement', 'id_equipement');
    }

    public function appui(): BelongsTo
    {
        return $this->belongsTo(Appui::class, 'id_appui', 'id_appui');
    }
}
