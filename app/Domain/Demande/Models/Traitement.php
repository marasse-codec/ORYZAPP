<?php

namespace App\Domain\Demande\Models;

use App\Domain\Referentiel\Models\Service;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Correspondance entre une demande et les services dont elle sollicite les
 * appuis : c'est elle qui dit quel service a accès à quelle demande.
 * Une ligne par couple (demande, service).
 */
#[Fillable('id_service', 'id_demande')]
#[Table('traitements')]
class Traitement extends Model
{
    protected $primaryKey = 'id_traitement';

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }

    /**
     * Rattache une demande à ses services, sans doublon.
     *
     * @param Collection<int, Service> $services
     */
    public static function creerTraitements(int $idDemande, Collection $services): void
    {
        foreach ($services as $service) {
            self::firstOrCreate([
                'id_demande' => $idDemande,
                'id_service' => $service->id_service,
            ]);
        }
    }
}
