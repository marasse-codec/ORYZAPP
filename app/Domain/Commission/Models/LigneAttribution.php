<?php

namespace App\Domain\Commission\Models;

use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneAttribution extends Model
{
    protected $table = 'lignes_attributions';

    protected $primaryKey = 'id_ligne_attribution';

    protected $fillable = ['id_attribution', 'id_appui', 'quantite_attribuee', 'cout_attribution', 'remise'];

    protected $casts = [
        'quantite_attribuee' => 'decimal:2',
        'cout_attribution' => 'decimal:2',
        'remise' => 'decimal:2',
    ];

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(Attribution::class, 'id_attribution', 'id_attribution');
    }

    public function appui(): BelongsTo
    {
        return $this->belongsTo(Appui::class, 'id_appui', 'id_appui');
    }

    /** Quantité × coût, diminué de la remise consentie par la commission. */
    public function montant(): float
    {
        return $this->quantite_attribuee * (float) $this->cout_attribution * (1 - (float) $this->remise / 100);
    }
}
