<?php

namespace App\Domain\Demande\Models;

use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneDemande extends Model
{
    use HasFactory;
    
    protected $table = 'lignes_demandes';

    protected $primaryKey = 'id_ligne_demande';

    protected $fillable = ['id_demande', 'id_type_appui', 'cession', 'id_appui', 'quantite_demandee'];

    protected $casts = [
        'quantite_demandee' => 'decimal:4',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    /** L'appui sollicité. Son type et son service se lisent à travers lui. */
    public function typeAppui(): BelongsTo
    {
        return $this->belongsTo(TypeAppui::class, 'id_type_appui', 'id_type_appui');
    }

    public function appui(): BelongsTo
    {
        return $this->belongsTo(Appui::class, 'id_appui', 'id_appui');
    }

}
