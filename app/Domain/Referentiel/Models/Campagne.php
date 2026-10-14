<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Demande\Models\Demande;
use App\Domain\Shared\Contracts\EstUtilisable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('lib_campagne','mois_debut_campagne','mois_fin_campagne')]
#[Table('campagnes')]
class Campagne extends Model implements EstUtilisable
{
    use HasFactory;
    
    protected $primaryKey = 'id_campagne';

    protected $casts = [
        'mois_debut_campagne' => 'date',
        'mois_fin_campagne' => 'date'
    ];
    
    /**
     * La campagne dont les dates couvrent aujourd'hui (la plus récente si deux
     * se touchent), à défaut la dernière campagne ouverte.
     */
    public static function enCours(): ?self
    {
        return self::whereDate('mois_debut_campagne', '<=', today())
            ->whereDate('mois_fin_campagne', '>=', today())
            ->orderByDesc('mois_debut_campagne')
            ->first()
            ?? self::orderByDesc('mois_debut_campagne')->first();
    }

    public function demandes() : HasMany {
        return $this->hasMany(Demande::class, 'id_campagne','id_campagne');
    }

    /** Utilisé : on ne le supprime pas. */
    public function estUtilise(): bool
    {
        return $this->demandes()->exists();
    }
}
