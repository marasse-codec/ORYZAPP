<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Shared\Contracts\EstUtilisable;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_appui
 * @property string $code_unite
 * @property string $lib_unite
 * \App\Domain\Referentiel\Models\TypeAppui|null $typeAppui
 */
class UniteDosage extends Model implements EstUtilisable
{
    use HasFactory;

    protected $table = 'unites_dosages';
    protected $primaryKey = 'id_unite';
    protected $fillable = [
        'code_unite',
        'lib_unite'
    ];

    public function referencesAppuis() : HasMany {
        
        return $this->hasMany(Appui::class,'id_unite','id_unite');
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->referencesAppuis()->exists();
    }
}
