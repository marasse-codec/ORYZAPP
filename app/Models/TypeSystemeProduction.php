<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeSystemeProduction extends Model
{
    protected $table = 'types_systemes_productions';

    protected $primaryKey = 'id_type_systeme_production';

    protected $fillable = ['lib_type_systeme_production'];

    public function systemesProduction(): HasMany
    {
        return $this->hasMany(SystemeProduction::class, 'id_type_systeme_production', 'id_type_systeme_production');
    }
}
