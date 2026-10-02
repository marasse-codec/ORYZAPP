<?php

namespace App\Models;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\StatutAttributionEnum;
use App\Observers\AttributionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[ObservedBy([AttributionObserver::class])]
class Attribution extends Model
{
    protected $primaryKey = 'id_attribution';

    protected $fillable = [
        'id_demande',
        'id_session',
        'commentaires',
        'remise',
        'date_destockage',
        'numero_piece_identite',
        'chemin_piece_identite',
        // 'id_statut_attribution',
        'date_publication',
        // 'id_traitement',
        'id_user'
    ];

    protected $casts = [
        'date_destockage' => 'date',
        'date_publication' => 'datetime',
        'remise' => 'decimal:2',
        // 'id_statut_attribution' => StatutAttributionEnum::class,
    ];

    public $afterCommit = true; 

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

    // public function traitement() {
    //     return $this->belongsTo(Traitement::class, 'id_traitement','id_traitement');
    // }

    public function user() {
         return $this->belongsTo(User::class, 'id_user','id_user');
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

    public function scopeUser($query)
    {
        $user = Auth::user();

        // if ($user?->hasRole(RoleEnum::agentCommission->value)) {
        //     return $query->whereNot('id_statut_attribution', StatutAttributionEnum::brouillon);
        if ($user?->hasRole(RoleEnum::dp->value)) {
            return $query->whereNotNull('date_publication')
                ->whereHas('demande.user', fn ($q) => $q->where('id', $user->id));
        } else if ($user->hasRole(RoleEnum::agentService->value)) {
            return $query->whereHas('lignesAttributions.appui.typeAppui', fn ($q) => $q->where('id_service', $user->id_service));
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
        $this->delete();
    }

    public function publier()
    {
        if($this->estPubliee()) return;

        $this->date_publication = now();
        $this->save();
    }

    public function estPubliee() : bool {
        return ! is_null($this->date_publication);
    }


    public function scopePubliables($query, $idSession){
        return $query->where('id_session',$idSession)->whereNull('date_publication');
    }

    // public function estSoumise(){
    //     return $this->id_statut_attribution == StatutAttributionEnum::soumise;
    // }

    /**
     * Vérifie si l'attribution est modifiable
     */
    public function estModifiable(){
        
        // Modifiable par rapport à l'attribution elle même
        return ! self::estPubliee();
    }


    public function soumettre(){
        $this->id_statut_attribution = StatutAttributionEnum::soumise->value;
        $this->save();
    }

}