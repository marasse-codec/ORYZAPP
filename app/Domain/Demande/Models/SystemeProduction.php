<?php

namespace App\Domain\Demande\Models;

use App\Domain\Referentiel\Models\TypeSystemeProduction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemeProduction extends Model
{
    protected $table = 'systemes_productions';

    protected $primaryKey = 'id_systeme_production';

    protected $fillable = ['id_demande', 'id_type_systeme_production', 'superficie'];

    protected $casts = ['superficie' => 'decimal:2'];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function typeSystemeProduction(): BelongsTo
    {
        return $this->belongsTo(TypeSystemeProduction::class, 'id_type_systeme_production', 'id_type_systeme_production');
    }
}
