<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Demande\Models\SystemeProduction;
use App\Domain\Shared\Contracts\EstUtilisable;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeSystemeProduction extends Model implements EstUtilisable
{
    protected $table = 'types_systemes_productions';

    protected $primaryKey = 'id_type_systeme_production';

    protected $fillable = ['lib_type_systeme_production'];

    public function systemesProduction(): HasMany
    {
        return $this->hasMany(SystemeProduction::class, 'id_type_systeme_production', 'id_type_systeme_production');
    }

    public function appuis(): BelongsToMany {
        return $this->belongsToMany(Appui::class,'appuis_types_systemes_productions','id_type_systeme_production','id_appui')->withTimestamps();
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->systemesProduction()->exists() || $this->appuis()->exists();
    }
}
