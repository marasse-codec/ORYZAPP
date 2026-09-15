<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('types_cultures')]
#[Fillable(['id_type_culture', 'lib_type_culture'])]
class TypeCulture extends Model
{
    //

    public function beneficiaires(): BelongsToMany
    {
        return $this->belongsToMany(
            Beneficiaire::class,
            'beneficiaires_types_cultures',
            'id_type_culture',
            'id_beneficiaire'
        )->withPivot('superficie')->withTimestamps();
    }

    public function cultures(): HasMany
    {
        return $this->hasMany(Culture::class, 'id_type_culture', 'id_type_culture');
    }
}
