<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Shared\Contracts\EstUtilisable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model implements EstUtilisable
{
    use HasFactory;

    protected $table = 'departements';

    protected $primaryKey = 'id_departement';

    protected $fillable = ['lib_departement'];

    public function localites(): HasMany
    {
        return $this->hasMany(Localite::class, 'id_departement', 'id_departement');
    }

    /** Les délégués de pôle responsables de ce département. */
    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'departements_users', 'id_departement', 'user_id');
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->localites()->exists();
    }
}
