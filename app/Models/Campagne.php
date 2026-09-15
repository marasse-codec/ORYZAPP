<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('lib_campagne','date_debut_campagne','date_fin_campagne')]
#[Table('campagnes')]
class Campagne extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'id_campagne';
    //

    protected $casts = [
        'date_debut_campagne' => 'date',
        'date_fin_campagne' => 'date'
    ];

    public function demandes() : HasMany {
        return $this->hasMany(Demande::class, 'id_campagne','id_campagne');
    }
}
