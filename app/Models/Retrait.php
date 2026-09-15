<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retrait extends Model
{
    protected $table = 'retraits';

    protected $primaryKey = 'id_retrait';

    protected $fillable = ['id_attribution', 'date_retrait', 'cni'];

    public function reponse(): BelongsTo
    {
        return $this->belongsTo(Attribution::class, 'id_attribution', 'id_attribution');
    }
}
