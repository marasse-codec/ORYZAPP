<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'id_service';

    protected $fillable = ['lib_service'];

    public function typesAppuis(): HasMany
    {
        return $this->hasMany(TypeAppui::class, 'id_service', 'id_service');
    }

    public function traitements(): HasMany
    {
        return $this->hasMany(Traitement::class, 'id_service', 'id_service');
    }
}
