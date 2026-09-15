<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appui extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_appui';

    protected $fillable = ['libelle', 'id_type_appui', 'quantite_en_stock', 'cout_unitaire', 'id_unite_appui'];

    protected $casts = ['cout_unitaire' => 'decimal:2'];

    /**
     * Les appuis réellement quantifiables. Conseils, études et autres
     * prestations immatérielles sortent du circuit : rien à stocker, rien à
     * destocker.
     */
    public function scopeQuantifiables($query)
    {
        return $query->whereHas('typeAppui', fn ($q) => $q->where('est_stockable', true));
    }

    public function typeAppui(): BelongsTo
    {
        return $this->belongsTo(TypeAppui::class, 'id_type_appui', 'id_type_appui');
    }

    public function approvisionnements(): HasMany
    {
        return $this->hasMany(Approvisionnement::class, 'id_appui', 'id_appui');
    }

    public function lignesAttributions(): HasMany
    {
        return $this->hasMany(LigneAttribution::class, 'id_appui', 'id_appui');
    }

    public function uniteAppui(): BelongsTo
    {
        return $this->belongsTo(UniteAppui::class, 'id_unite_appui', 'id_unite_appui');
    }

    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class, 'id_appui', 'id_appui');
    }
}
