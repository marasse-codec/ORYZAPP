<?php

namespace App\Models;

use App\Enums\TypeMouvementStockEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('matricule_equipement', 'etat', 'en_service', 'id_appui')]
#[Table('equipements')]
class Equipement extends Model
{

    protected $primaryKey = 'id_equipement';

    public function mouvementsStocks()
    {
        return $this->hasMany(MouvementStock::class, 'id_equipement', 'id_equipement');
    }

    public function appui(): BelongsTo
    {
        return $this->belongsTo(Appui::class, 'id_appui', 'id_appui');
    }

    /**
     * Retourne l'attribution (ou le mouvement de sortie) qui a conduit au prêt de l'équipement.
     *
     * @return \App\Models\MouvementStock|null
     */
    public function aAttributionEnCours()
    {
        // Rechercher le dernier mouvement de sortie de l'équipement
        $mouvement = $this->mouvementsStocks()
            ->orderByDesc('date_mouvement')
            ->first();

        return $mouvement->id_type_mouvement_stock === TypeMouvementStockEnum::SORTIE;
    }

    /**
     * Retourne l'attribution (ou le mouvement de sortie) qui a conduit au prêt de l'équipement.
     *
     * @return \App\Models\MouvementStock|null
     */
    public function scopeAttributionEnCours($query)
    {
        // Rechercher le dernier mouvement de sortie de l'équipement
        return $query->whereHas('mouvements', fn ($q) => $q->where('id_type_mouvement_stock',TypeMouvementStockEnum::SORTIE));
    }

}
