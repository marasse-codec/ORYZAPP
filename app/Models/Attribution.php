<?php

namespace App\Models;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\StatutAttributionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Attribution extends Model
{
    protected $primaryKey = 'id_attribution';

    protected $fillable = [
        'id_demande',
        'id_session',
        'commentaires',
        'remise',
        'date_destockage',
        'recipiendaire',
        'numero_piece_identite',
        'chemin_piece_identite',
        'id_statut_attribution',
        'date_publication',
    ];

    protected $casts = [
        'date_destockage' => 'date',
        'date_publication' => 'datetime',
        'remise' => 'decimal:2',
        'id_statut_attribution' => StatutAttributionEnum::class,
    ];

    // ---------------------------------------------------------------- Relations

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    /** Correction : BelongsTo au lieu de HasOne */
    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionCommission::class, 'id_session', 'id_session');
    }

    public function lignesAttributions(): HasMany
    {
        return $this->hasMany(LigneAttribution::class, 'id_attribution', 'id_attribution');
    }

    // ------------------------------------------------------------------ Métier

    public function estDestockable(): bool
    {
        $this->loadMissing('session');

        return $this->date_destockage === null && ! $this->session?->estOuverte();
    }

    public function montantTotal(): float
    {
        $this->loadMissing('lignesAttributions');

        return (float) $this->lignesAttributions->sum(fn (LigneAttribution $ligne) => $ligne->montant());
    }

    // ------------------------------------------------------------------ Scopes

    public function scopeUserService($query, $idService)
    {
        $user = Auth::user();

        if ($user?->can(PermissionEnum::gererCommission->value)) {
            return $query->whereNot('id_statut_attribution', StatutAttributionEnum::brouillon);
        }

        if ($user?->hasRole(RoleEnum::dp->value)) {
            return $query->whereNotNull('date_publication')
                ->whereHas('demande.user', fn ($q) => $q->where('id', $user->id));
        }

        /** Correction : filtre si $idService est présent et ciblage de id_statut_attribution sur la table attributions */
        if ($idService) {
            return $query->where('id_statut_attribution', StatutAttributionEnum::brouillon)
                ->whereHas('lignesAttributions.appui.typeAppui', fn ($q) => $q->where('id_service', $idService));
        }

        return $query;
    }

    public function valider($idSession)
    {
        if ($this->id_statut_attribution === StatutAttributionEnum::soumise) {
            $this->id_statut_attribution = StatutAttributionEnum::validee;
            $this->id_session = $idSession;
            $this->save();
        }
    }

    public function annuler($idSession)
    {
        if ($this->id_statut_attribution === StatutAttributionEnum::soumise) {
            $this->id_statut_attribution = StatutAttributionEnum::annulee;
            $this->id_session = $idSession;
            $this->save();
        }
    }

    public function publier()
    {
        $this->date_publication = now();
        $this->save();
    }
}