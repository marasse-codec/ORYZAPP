<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('lib_statut_attribution')]
#[Table('statuts_attributions')]
class StatutAttribution extends Model
{
    //

    public function attributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'id_statut_attribution', 'id_statut_attribution');
    }
}
