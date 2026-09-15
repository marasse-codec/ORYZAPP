<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    use HasFactory;

    protected $table = 'departements';

    protected $primaryKey = 'id_departement';

    protected $fillable = ['lib_departement'];

    public function localites(): HasMany
    {
        return $this->hasMany(Localite::class, 'id_departement', 'id_departement');
    }
}
