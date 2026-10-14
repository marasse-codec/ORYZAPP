<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Demande\Models\Traitement;
use App\Domain\Shared\Contracts\EstUtilisable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model implements EstUtilisable
{
    use HasFactory;
    
    protected $primaryKey = 'id_service';

    protected $fillable = ['lib_service'];

    public function typesAppuis(): HasMany
    {
        return $this->hasMany(TypeAppui::class, 'id_service', 'id_service');
    }

    public function traitements(): HasMany
    {
        return $this->hasMany(Traitement::class, 'id_service', 'id_service');
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->typesAppuis()->exists() || $this->traitements()->exists() || User::where('id_service', $this->id_service)->exists();
    }
}
