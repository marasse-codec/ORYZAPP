<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_culture', 'lib_culture', 'id_type_culture', 'id_beneficiaire', 'superficie'])]
class Culture extends Model
{
    protected $primaryKey = 'id_culture';

    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(Beneficiaire::class, 'id_beneficiaire', 'id_beneficiaire');
    }

    public function typeCulture(): BelongsTo
    {
        return $this->belongsTo(TypeCulture::class, 'id_type_culture', 'id_type_culture');
    }
}
