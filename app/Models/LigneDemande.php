<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneDemande extends Model
{
    use HasFactory;
    
    protected $table = 'lignes_demandes';

    protected $primaryKey = 'id_ligne_demande';

    protected $fillable = ['id_demande', 'id_appui', 'quantite_demandee','date_rejet_service','motif_rejet_service'];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    /** L'appui sollicité. Son type et son service se lisent à travers lui. */
    public function appui(): BelongsTo
    {
        return $this->belongsTo(Appui::class, 'id_appui', 'id_appui');
    }

    public function rejeterLigne($motifRejet){

        if(empty($motifRejet) || $motifRejet === null){
            return;
        }

        $this->update([
            'date_rejet_service' => now(),
            'motif_rejet_service' => $motifRejet
        ]);

        JournalAction::enregistrerRejetDemandeService($this->id_demande);

    }

}
