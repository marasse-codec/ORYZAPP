<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiaire extends Model
{
    protected $primaryKey = 'id_beneficiaire';

    protected $fillable = [
        'id_demande',
        'id_acteur',
        'telephone',
        'email',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(Acteur::class, 'id_acteur', 'id_acteur');
    }

    public function cultures(): HasMany
    {
        return $this->hasMany(Culture::class, 'id_beneficiaire', 'id_beneficiaire');
    }
}
