<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('motif', 'date_demande_correction', 'id_traitement', 'id_demande')]
#[Table('demandes_corrections')]
class DemandeCorrection extends Model
{
    //
    protected $primaryKey = 'id_demande_correction';

    protected $casts = [
        'date_demande_correction' => 'datetime',
    ];

    public function traitement(): BelongsTo
    {

        return $this->belongsTo(Traitement::class, 'id_traitement', 'id_traitement');

    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(
            Demande::class,
            'id_demande',
            'id_demande'
        );
    }
}
