<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('code_camapagne','lib_campagne','mois_debut_campagne','mois_fin_campagne')]
#[Table('campagnes')]
class Campagne extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'id_campagne';

    protected $casts = [
        'mois_debut_campagne' => 'date',
        'mois_fin_campagne' => 'date'
    ];
    
    public function demandes() : HasMany {
        return $this->hasMany(Demande::class, 'id_campagne','id_campagne');
    }
}
