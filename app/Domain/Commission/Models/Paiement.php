<?php

namespace App\Domain\Commission\Models;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un règlement, total ou partiel, d'une attribution.
 *
 * Il naît en brouillon : le comptable peut le corriger ou le supprimer. Une fois
 * soumis (date_soumission renseignée), il est définitif et compte dans ce que
 * l'acteur a payé.
 */
class Paiement extends Model
{
    protected $primaryKey = 'id_paiement';

    protected $fillable = ['id_attribution', 'montant', 'date_paiement', 'mode', 'reference', 'id_user', 'date_soumission', 'id_user_soumission'];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
        'date_soumission' => 'datetime',
    ];

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(Attribution::class, 'id_attribution', 'id_attribution');
    }

    /** Qui a saisi le règlement. */
    public function encaissePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    /** Qui l'a soumis (validé). */
    public function soumisPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_soumission', 'id');
    }

    public function modeEnum(): ?ModePaiementEnum
    {
        return ModePaiementEnum::tryFrom($this->mode);
    }

    public function estSoumis(): bool
    {
        return $this->date_soumission !== null;
    }

    /** Les règlements définitifs. */
    public function scopeSoumis($query)
    {
        return $query->whereNotNull('date_soumission');
    }

    /** Les règlements encore en brouillon. */
    public function scopeBrouillons($query)
    {
        return $query->whereNull('date_soumission');
    }
}
