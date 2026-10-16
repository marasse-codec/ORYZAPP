<?php

namespace App\Domain\Referentiel\Models;

use App\Domain\Demande\Models\Demande;
use App\Domain\Shared\Contracts\EstUtilisable;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lib_campagne', 'mois_debut_campagne', 'mois_fin_campagne',
    // v2 : clôture
    'cloturee_le', 'id_user_cloture',
])]
#[Table('campagnes')]
class Campagne extends Model implements EstUtilisable
{
    use HasFactory;
    
    protected $primaryKey = 'id_campagne';

    protected $casts = [
        'mois_debut_campagne' => 'date',
        'mois_fin_campagne' => 'date',
        'cloturee_le' => 'datetime',
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

    /**
     * L'année d'un cycle : celle de son début. Un cycle de novembre 2026 à février 2027
     * appartient à 2026. On choisit d'abord l'année, puis l'un de ses cycles.
     */
    public function annee(): int
    {
        return (int) $this->mois_debut_campagne->year;
    }

    /** « Cycle 2 (2026) » : le cycle et son année, pour les listes de choix. */
    public function libelleComplet(): string
    {
        return "{$this->lib_campagne} ({$this->annee()})";
    }

    /** Les cycles d'une année, du premier au dernier. */
    public function scopeDeLAnnee($query, int $annee)
    {
        return $query->whereYear('mois_debut_campagne', $annee)->orderBy('mois_debut_campagne');
    }

    /**
     * Les années qui ont au moins un cycle, de la plus récente à la plus ancienne.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function annees()
    {
        return self::orderByDesc('mois_debut_campagne')->get(['mois_debut_campagne'])
            ->map(fn (self $c) => $c->annee())
            ->unique()
            ->values();
    }

    /** Les campagnes encore ouvertes : seules elles acceptent de nouvelles demandes. */
    public function scopeOuvertes($query)
    {
        return $query->whereNull('cloturee_le');
    }

    /** Qui a clôturé la campagne. */
    public function clotureePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_cloture', 'id');
    }

    /** Une campagne clôturée est figée : ses chiffres ne bougent plus. */
    public function estCloturee(): bool
    {
        return $this->cloturee_le !== null;
    }

    /**
     * Refuse une opération sur une campagne clôturée, en disant pourquoi.
     * Appelée par chaque cas d'usage qui modifie une demande, une attribution ou un rendement.
     */
    public function exigerOuverte(string $operation): void
    {
        if ($this->estCloturee()) {
            throw new RegleMetierViolee("La campagne « {$this->lib_campagne} » est clôturée : {$operation} n'est plus possible.");
        }
    }

    /** Même contrôle à partir d'un identifiant (utile quand on n'a que l'id de la demande). */
    public static function exigerOuverteParId(int|string|null $idCampagne, string $operation): void
    {
        if ($idCampagne !== null) {
            self::find($idCampagne)?->exigerOuverte($operation);
        }
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
