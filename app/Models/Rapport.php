<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rapport extends Model
{
    protected $primaryKey = 'id_rapport';

    protected $fillable = ['id_demande', 'id_service', 'commentaire', 'date_rapport', 'accord_service'];

    protected $casts = [
        'date_rapport' => 'date',
        'accord_service' => 'date',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }

    /** Le service a autorisé le passage en commission. */
    public function estAccorde(): bool
    {
        return $this->accord_service !== null;
    }
}
