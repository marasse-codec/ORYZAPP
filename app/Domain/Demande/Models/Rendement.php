<?php

namespace App\Domain\Demande\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Le retour de fin de campagne sur une superficie déclarée : ce qu'elle a produit. */
class Rendement extends Model
{
    protected $primaryKey = 'id_rendement';

    protected $fillable = ['id_systeme_production', 'superficie_recoltee', 'production_tonnes', 'id_user'];

    protected $casts = [
        'superficie_recoltee' => 'decimal:2',
        'production_tonnes' => 'decimal:3',
    ];

    public function systemeProduction(): BelongsTo
    {
        return $this->belongsTo(SystemeProduction::class, 'id_systeme_production', 'id_systeme_production');
    }

    public function transmisPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    /** Tonnes par hectare ; null si rien n'a été récolté sur la surface (division impossible). */
    public function getRendementTHaAttribute(): ?float
    {
        $superficie = (float) $this->superficie_recoltee;

        return $superficie > 0 ? round((float) $this->production_tonnes / $superficie, 2) : null;
    }
}
