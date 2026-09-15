<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_direction', 'lib_direction', 'requiert_commission'])]
class Direction extends Model
{
    protected $primaryKey = 'id_direction';

    protected $fillable = [
        'lib_direction',
        'requiert_commission',
    ];

    protected $casts = [
        'requiert_commission' => 'boolean',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'id_direction', 'id_direction');
    }
}
