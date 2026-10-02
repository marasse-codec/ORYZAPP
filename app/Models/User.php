<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\RoleEnum;
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

#[Fillable(['name', 'email', 'password', 'id_service'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
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
}
