<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Localite extends Model
{
    use HasFactory;

    protected $table = 'localites';
    protected $primaryKey = 'id_localite';

    // CORRECTION : Remplacement du point par une virgule
    protected $fillable = ['lib_localite', 'id_departement'];

    public function departement(): BelongsTo 
    {
        return $this->belongsTo(Departement::class, 'id_departement', 'id_departement');
    }

    public function demandes(): BelongsToMany
    {
        // CORRECTION : Précision des clés étrangères (id_localite, id_demande)
        return $this->belongsToMany(
            Demande::class, 
            'demandes_localites', 
            'id_localite', 
            'id_demande'
        );
    }
}