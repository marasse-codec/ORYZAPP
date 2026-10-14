<?php

namespace App\Domain\Commission\Models;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un règlement, total ou partiel, d'une attribution. */
class Paiement extends Model
{
    protected $primaryKey = 'id_paiement';

    protected $fillable = ['id_attribution', 'montant', 'date_paiement', 'mode', 'reference', 'id_user'];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(Attribution::class, 'id_attribution', 'id_attribution');
    }

    public function encaissePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function modeEnum(): ?ModePaiementEnum
    {
        return ModePaiementEnum::tryFrom($this->mode);
    }
}
