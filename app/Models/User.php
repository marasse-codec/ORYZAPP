<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Service;
use App\Domain\Shared\Contracts\EstUtilisable;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Models\Agent;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'id_service', 'actif', 'desactive_le', 'motif_desactivation', 'derniere_connexion_le'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements EstUtilisable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable,HasPushSubscriptions;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
            'desactive_le' => 'datetime',
            'derniere_connexion_le' => 'datetime',
        ];
    }

    public function acteur(): HasOne
    {
        return $this->hasOne(Acteur::class, 'user_id', 'id');
    }

    /** Le service dont relève l'agent, s'il en est un. */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }

    public function demandes(): HasMany 
    {
        return $this->hasMany(Demande::class, 'id_user', 'id');
    }

    public function attributions() : HasMany {
        return $this->hasMany(Attribution::class, 'id_user', 'id_user');   
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class,'user_id','id');
    }

    /**
     * Forcer le canal WebPush à récupérer une collection d'abonnements valide
     */
    public function routeNotificationForWebPush()
    {
        return $this->pushSubscriptions;
    }

    /**
     * L'agent de stock rattaché à ce compte : un mouvement de stock est signé
     * par un agent. Sans lui, on refuse plutôt que d'attribuer le mouvement à un autre.
     */
    public function idAgent(): int
    {
        $idAgent = $this->agent?->id_agent;

        if ($idAgent === null) {
            throw new RegleMetierViolee("Votre compte n'est lié à aucun agent : impossible d'enregistrer un mouvement de stock.");
        }

        return $idAgent;
    }

    public function getAgentsCommissions() : Collection
    {
        // Si votre enum retourne le nom du rôle (ex: RoleEnum::agentCommission->value) 
        // ou le modèle / ID du rôle :
        return User::role(RoleEnum::agentCommission)->get();
    }

    /** 
     * Renvoi si l'utilisateur peut modifier une attribution
     */
    public function peutModifierAttribution() : bool {
        
        // Modifiable par l'utilisateur du contexte
        if(! auth()->user()->hasRole(RoleEnum::agentCommission->value)) return false;

        return true;
    }

    /** Un compte désactivé garde son historique mais ne peut plus se connecter. */
    public function estActif(): bool
    {
        return $this->actif !== false;
    }

    /** Les comptes qui peuvent se connecter. */
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    /** Le rôle unique du compte (un compte n'a qu'un rôle), ou null s'il n'en a pas. */
    public function roleEnum(): ?RoleEnum
    {
        return RoleEnum::tryFrom((string) $this->getRoleNames()->first());
    }

    /**
     * Un compte qui a agi (demandes, journal) ne se supprime pas : les demandes
     * partiraient avec lui, et le journal perdrait son auteur.
     */
    public function estUtilise(): bool
    {
        return $this->demandes()->exists() || JournalAction::where('user_id', $this->id)->exists();
    }
}
