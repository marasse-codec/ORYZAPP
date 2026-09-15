<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniteAppui extends Model
{
    use HasFactory;
    
    protected $table = 'unites_appuis';

    protected $primaryKey = 'id_unite_appui';

    protected $fillable = ['lib_unite_appui'];

    public function appuis(): HasMany
    {
        return $this->hasMany(Appui::class, 'id_unite_appui', 'id_unite_appui');
    }
}
