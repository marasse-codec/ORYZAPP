<?php

namespace App\Domain\Stock\Models;

use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('code', 'libelle_type_mouvement')]
#[Table('types_mouvements_stocks')]
class TypeMouvementStock extends Model
{
    protected $primaryKey = 'id_type_mouvement_stock';

    public function mouvementsStocks(): HasMany
    {
        return $this->hasMany(MouvementStock::class, 'id_type_mouvement_stock', 'id_type_mouvement_stock');
    }

    /** La clé en base d'un type de mouvement, retrouvée par son code : aucun identifiant n'est écrit en dur. */
    public static function idDe(TypeMouvementStockEnum $type): int
    {
        return (int) static::where('code', $type->value)->firstOrFail()->getKey();
    }
}
