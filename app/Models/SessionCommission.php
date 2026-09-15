<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionCommission extends Model
{
    protected $table = 'sessions_commission';

    protected $primaryKey = 'id_session';

    protected $fillable = ['resume', 'date_debut_session', 'date_fin_session'];

    protected $casts = [
        'date_debut_session' => 'date',
        'date_fin_session' => 'date',
    ];

    public function attributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'id_session', 'id_session');
    }

    /** Tant que la séance n'est pas clôturée, elle peut recevoir des dossiers. */
    public function estOuverte(): bool
    {
        return $this->date_fin_session === null;
    }
}
