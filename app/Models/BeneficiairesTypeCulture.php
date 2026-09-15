<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiairesTypeCulture extends Model
{
    protected $table = 'beneficiaires_types_cultures';

    protected $fillable = [
        'id_beneficiaire',
        'id_type_culture',
        'superficie',
    ];

    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(Beneficiaire::class, 'id_beneficiaire', 'id_beneficiaire');
    }

    public function typeCulture(): BelongsTo
    {
        return $this->belongsTo(TypeCulture::class, 'id_type_culture', 'id_type_culture');
    }
}
