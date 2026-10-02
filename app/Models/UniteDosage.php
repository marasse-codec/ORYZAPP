<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_appui
 * @property string $code_unite
 * @property string $lib_unite
 * \App\Models\TypeAppui|null $typeAppui
 */
class UniteDosage extends Model
{
    //

    protected $table = 'unites_dosages';
    protected $fillable = [
        'code_unite',
        'lib_unite'
    ];

    public function referencesAppuis() : HasMany {
        
        return $this->hasMany(Appui::class,'id_unite','id_unite');
    }
}
