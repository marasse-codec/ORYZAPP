<?php

namespace App\Domain\Commission\Models;

use App\Domain\Referentiel\Models\TypeSystemeProduction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** La superficie qu'une attribution permet de traiter, pour une écologie de la demande. */
class SuperficieAttribution extends Model
{
    protected $table = 'attributions_systemes_productions';

    protected $primaryKey = 'id_attribution_systeme_production';

    protected $fillable = ['id_attribution', 'id_type_systeme_production', 'superficie_traitee'];

    protected $casts = ['superficie_traitee' => 'decimal:2'];

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(Attribution::class, 'id_attribution', 'id_attribution');
    }

    public function typeSystemeProduction(): BelongsTo
    {
        return $this->belongsTo(TypeSystemeProduction::class, 'id_type_systeme_production', 'id_type_systeme_production');
    }
}
