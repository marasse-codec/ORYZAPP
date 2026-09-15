<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('locations_appuis')]
#[Fillable(['date_debut_location', 'date_fin_location', 'id_ligne_attribution'])]
class LocationAppui extends Model
{
    public function ligneAttribution(): BelongsTo
    {
        return $this->belongsTo(LigneAttribution::class, 'id_ligne_attribution', 'id_ligne_attribution');
    }
}
