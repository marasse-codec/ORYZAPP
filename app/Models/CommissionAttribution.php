<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionAttribution extends Model
{
    protected $table = 'commissions_attributions';

    protected $primaryKey = 'id_commission';

    protected $fillable = ['date_ouverture_commission', 'date_fermeture_commission'];

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'id_commission', 'id_commission');
    }

    /** Tant qu'elle n'est pas clôturée, la séance accepte de nouvelles copies. */
    public function estOuverte(): bool
    {
        return $this->date_fermeture_commission === null;
    }

    public function publiable(){

        // Recherche attributions publiables
        $attributions = Attribution::whereNull('date_publication')->where('id_session',$this->id_session)->first();

        if(! is_null($this->date_fin_session) && ! count($attributions) === 0) return true;

        return false;
    }
}
