<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable(
    'date_fin_traitement',
    'observation_interne',
    'motifs_rejet',
    'id_service',
    'id_demande',
    'id_traitement'
)]
#[Table('traitements')]
class Traitement extends Model
{
    protected $primaryKey = 'id_traitement';

    protected $casts = [
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

    public function attribution(): HasOne {
        return $this->hasOne(Attribution::class,'id_traitement','id_traitement');
    }

    /** METIER */

    /**
     * Crée les traitements pour une demande et une liste de services.
     *
     * @param int $idDemande
     * @param Collection<int, Service> $services
     * @return void
     */
    public static function creerTraitements(int $idDemande, Collection $services): void
    {
        foreach ($services as $service) {
            Traitement::firstOrCreate([
                'id_demande' => $idDemande,
                'id_service' => $service->id_service,
            ]);
        }
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
