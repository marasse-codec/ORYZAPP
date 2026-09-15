<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class JournalAction extends Model
{
    use HasFactory;

    protected $table = 'journals_actions';

    protected $fillable = [
        'user_id',
        'action',
        'cible_type',
        'cible_id',
    ];

    /**
     * Relation polymorphique inverse pour récupérer la cible (Demande, etc.)
     */
    public function cible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation vers l'auteur de l'action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //------------------------Metier----------------------//

    public static function enregistrerRejetDemande($idCible){
        $idUser = Auth::user()->id;
        $action = "REJET_DEMANDE";
        $typeCible = Demande::class;
        
        self::create([
            'user_id' => $idUser,
            'action' => $action,
            'cible_type' => $typeCible,
            'cible_id' => $idCible
        ]);
    }

    public static function enregistrerRejetDemandeService($idCible){
        $idUser = Auth::user()->id;
        $action = "REJET_DEMANDE_SERVICE";
        $typeCible = Demande::class;
        
        self::create([
            'user_id' => $idUser,
            'action' => $action,
            'cible_type' => $typeCible,
            'cible_id' => $idCible
        ]);
    }

}