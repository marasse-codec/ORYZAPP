<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Localite extends Model
{
    use HasFactory;

    protected $table = 'localites';

    protected $primaryKey = 'id_localite';

    protected $fillable = ['lib_localite', 'id_departement'];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'id_departement', 'id_departement');
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'id_localite', 'id_localite');
    }
}
