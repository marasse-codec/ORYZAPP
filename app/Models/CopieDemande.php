<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rattachement d'une demande à un service qui doit en recevoir copie.
 * Simple correspondance : ni la demande ni ses lignes ne sont dupliquées.
 */
class CopieDemande extends Model
{
    protected $table = 'copies_demandes';

    protected $primaryKey = 'id_copie_demande';

    protected $fillable = ['id_demande', 'id_service'];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }

    /** La séance qui a examiné cette copie, quand le service en dépend. */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(CommissionAttribution::class, 'id_commission', 'id_commission');
    }

    public function requiertCommission(): bool
    {
        return (bool) $this->service?->requiertCommission();
    }

    /**
     * Une copie ne se traite qu'une fois passée en commission, sauf pour les
     * directions qui en sont dispensées (la DI).
     */
    public function estAttribuable(): bool
    {
        return ! $this->requiertCommission() || $this->id_commission !== null;
    }

    /** Reste à inscrire à une séance : la commission lui manque encore. */
    public function attendCommission(): bool
    {
        return $this->requiertCommission() && $this->id_commission === null;
    }

    /** Les lignes de la demande qui relèvent du service destinataire. */
    public function lignes()
    {
        return $this->demande->lignesPourService($this->id_service);
    }
}
