<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('id_rejet', 'motif', 'date_rejet')]
#[Table('rejets')]
class Rejet extends Model
{
    //
    protected $casts = [
        'date_rejet' => 'datetime',
    ];

    public function traitement(): BelongsTo
    {

        return $this->belongsTo(Traitement::class, 'id_traitement', 'id_traitement');

    }
}
