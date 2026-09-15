<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(
    'date_fin_traitement',
    'observation_interne',
    'id_service',
    'id_demande'
)]
#[Table('traitements')]
class Traitement extends Model
{
    protected $primaryKey = 'id_traitement';

    protected $casts = [
        'date_prise_en_charge' => 'datetime',
        'date_fin_traitement' => 'datetime',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(
            Demande::class,
            'id_demande',
            'id_demande'
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class,
            'id_service',
            'id_service'
        );
    }

    public function demandesCorrections(): HasMany
    {
        return $this->hasMany(
            DemandeCorrection::class,
            'id_traitement',
            'id_traitement'
        );
    }

    public static function traitementTerminee($idDemande)
    {
        return Traitement::where('id_demande', $idDemande)->whereNotNull('date_fin_traitement')->get();
    }
}
