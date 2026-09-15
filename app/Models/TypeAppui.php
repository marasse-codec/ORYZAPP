<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAppui extends Model
{
    use HasFactory;
    
    protected $table = 'types_appuis';

    protected $primaryKey = 'id_type_appui';

    protected $fillable = ['libelle', 'est_intrant', 'est_stockable', 'id_service'];

    protected $casts = [
        'est_intrant' => 'boolean',
        'est_stockable' => 'boolean',
    ];

    public function appuis(): HasMany
    {
        return $this->hasMany(Appui::class, 'id_type_appui', 'id_type_appui');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }
}
