<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('lib_statut_demande')]
#[Table('statuts_demandes')]
class StatutDemande extends Model
{
    protected $primaryKey = 'id_statut_demande';

    //
    use HasFactory;

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'id_statut_demande', 'id_statut_demande');
    }
}
