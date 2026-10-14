<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Shared\Contracts\EstUtilisable;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAppui extends Model implements EstUtilisable
{
    use HasFactory;
    
    protected $table = 'types_appuis';

    protected $primaryKey = 'id_type_appui';

    protected $fillable = ['lib_type_appui', 'est_intrant', 'est_stockable', 'id_service'];

    protected $casts = [
        'est_intrant' => 'boolean',
        'est_stockable' => 'boolean',
    ];

    public function appuis(): HasMany
    {
        return $this->hasMany(Appui::class, 'id_type_appui', 'id_type_appui');
    }

    public function lignesDemandes(): HasMany
    {
        return $this->hasMany(LigneDemande::class, 'id_type_appui', 'id_type_appui');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->appuis()->exists() || $this->lignesDemandes()->exists();
    }
}
