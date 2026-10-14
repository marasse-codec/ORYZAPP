<?php

namespace App\Domain\Stock\Models;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Notification\Concerns\HasSubscriptions;
use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Referentiel\Models\TypeSystemeProduction;
use App\Domain\Referentiel\Models\UniteDosage;
use App\Domain\Shared\Contracts\EstUtilisable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Appui extends Model implements EstUtilisable
{
    use HasFactory, HasSubscriptions;
    

    protected $primaryKey = 'id_appui';

    protected $fillable = ['libelle', 'id_type_appui', 'quantite_en_stock', 'seuil_alerte', 'cout_unitaire', 'dose_ha', 'id_unite'];

    protected $casts = [
        'quantite_en_stock' => 'decimal:2',
        'seuil_alerte' => 'decimal:2',
        'cout_unitaire' => 'decimal:2',
        'dose_ha' => 'decimal:3'
        ];

    public function uniteDosage() : BelongsTo {
            return $this->belongsTo(UniteDosage::class,'id_unite','id_unite');
    }

    public function quantiteeEnStock(): int
    {
        // Accès dynamique à la relation typeAppui (?-> évite une erreur si typeAppui est null)
        if (! $this->typeAppui?->est_intrant) {
            // Option 1 : Si vous souhaitez compter UNIQUEMENT les équipements disponibles en stock
            return $this->equipements()->where('en_service', false)->count();

            // Option 2 : Si vous souhaitez compter TOUS les équipements
            // return $this->equipements()->count();
        }

        return (int) $this->quantite_en_stock;
    }

    /**
     * @param Builder $query
     */
    public function scopePourUtilisateur(Builder $query) : Builder {
        $user = Auth::user();

        if ($user?->hasRole(RoleEnum::agentService)){

            return $query->whereHas('typeAppui.service', function ($q) use ($user) {

            $q->where('id_service', $user->id_service);
            });
        }

        return $query;
    }

    public function typesSystemesProductions(): BelongsToMany {
        return $this->belongsToMany(TypeSystemeProduction::class,'appuis_types_systemes_productions','id_appui','id_type_systeme_production')->withTimestamps();
    }

    public function typeAppui(): BelongsTo
    {
        return $this->belongsTo(TypeAppui::class, 'id_type_appui', 'id_type_appui');
    }

    public function lignesDemandes(): HasMany{
        return $this->hasMany(LigneDemande::class, 'id_appui', 'id_appui');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class, 'id_appui', 'id_appui');
    }

    public function lignesAttributions(): HasMany
    {
        return $this->hasMany(LigneAttribution::class, 'id_appui', 'id_appui');
    }

    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class, 'id_appui', 'id_appui');
    }

    /**
     * Calcule la quantité totale engagée / attribuée pour cet appui.
     */
    public function quantiteEngagee(): int
    {
        // Si la relation est déjà chargée en mémoire (Eager Loading)
        if ($this->relationLoaded('lignesAttributions')) {
            return (int) $this->lignesAttributions->sum('quantite_attribuee');
        }

        // Sinon, calcul direct en BDD
        return (int) $this->lignesAttributions()->sum('quantite_attribuee');
    }

    public function estEquipement() : bool {
        return $this->equipements()->exists();
    }

    /**
     * Un appui demandé, attribué, entré ou sorti du magasin ne se supprime pas :
     * l'historique des dossiers et du stock s'y rattache.
     */
    public function estUtilise(): bool
    {
        return $this->lignesDemandes()->exists()
            || $this->lignesAttributions()->exists()
            || $this->mouvements()->exists()
            || $this->equipements()->exists();
    }
}
