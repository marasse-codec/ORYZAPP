<?php

namespace App\Domain\Stock\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable('nom', 'matricule_agent', 'actif', 'user_id')]
#[Table('agents')]
class Agent extends Model
{
    protected $primaryKey = 'id_agent';
    //

    public function mouvementsStocks()
    {
        return $this->hasMany(MouvementStock::class, 'id_agent', 'id_agent');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
