<?php

namespace App\Domain\Commission\Models;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Enums\StatutAttributionEnum;
use App\Domain\Demande\Models\Demande;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Attribution extends Model
{
    protected $primaryKey = 'id_attribution';

    protected $fillable = [
        'id_demande',
        'id_session',
        'commentaires',
        'remise',
        'date_destockage',
        'recipiendaire',
        'numero_piece_identite',
        'chemin_piece_identite',
        'date_publication',
        'id_user'
    ];

    protected $casts = [
        'date_destockage' => 'date',
        'date_publication' => 'datetime',
        'remise' => 'decimal:2',
    ];

    // ---------------------------------------------------------------- Relations

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    /** Correction : BelongsTo au lieu de HasOne */
    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionCommission::class, 'id_session', 'id_session');
    }

    public function lignesAttributions(): HasMany
    {
        return $this->hasMany(LigneAttribution::class, 'id_attribution', 'id_attribution');
    }

    public function superficies(): HasMany
    {
        return $this->hasMany(SuperficieAttribution::class, 'id_attribution', 'id_attribution');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'id_attribution', 'id_attribution');
    }

    public function user() {
         return $this->belongsTo(User::class, 'id_user','id_user');
    }

    // ------------------------------------------------------------------ Métier

    /** Le bon se sert une fois les résultats publiés, et une seule fois. */
    public function estDestockable(): bool
    {
        return $this->date_destockage === null && $this->estPubliee();
    }

    public function montantTotal(): float
    {
        $this->loadMissing('lignesAttributions');

        return (float) $this->lignesAttributions->sum(fn (LigneAttribution $ligne) => $ligne->montant());
    }

    /** Ce que l'acteur a déjà réglé. */
    public function totalPaye(): float
    {
        $this->loadMissing('paiements');

        return round((float) $this->paiements->sum('montant'), 2);
    }

    /**
     * Ce qu'il reste à régler. Rien n'est dû avant la publication des résultats :
     * jusque-là l'attribution peut encore changer.
     */
    public function resteDu(): float
    {
        if (! $this->estPubliee()) {
            return 0.0;
        }

        return max(0.0, round($this->montantTotal() - $this->totalPaye(), 2));
    }

    public function estReglee(): bool
    {
        return $this->resteDu() <= 0;
    }

    // ------------------------------------------------------------------ Scopes

    public function scopeUser($query)
    {
        $user = Auth::user();

        if ($user?->hasRole(RoleEnum::dp->value)) {
            return $query->whereNotNull('date_publication')
                ->whereHas('demande.user', fn ($q) => $q->where('id', $user->id));
        } else if ($user->hasRole(RoleEnum::agentService->value)) {
            return $query->whereHas('lignesAttributions.appui.typeAppui', fn ($q) => $q->where('id_service', $user->id_service));
        }

        return $query;
    }

    public function publier()
    {
        if($this->estPubliee()) return;

        $this->date_publication = now();
        $this->save();
    }

    public function estPubliee() : bool {
        return ! is_null($this->date_publication);
    }


    /** Résultats publiés et bon pas encore retiré au magasin (même règle que estDestockable). */
    public function scopeADestocker($query)
    {
        return $query->whereNull('date_destockage')->whereNotNull('date_publication');
    }

    /** Séance clôturée et attribution pas encore publiée (même règle que SessionCommission::estPubliable). */
    public function scopeAPublier($query)
    {
        return $query->whereNull('date_publication')
            ->whereHas('session', fn ($q) => $q->whereNotNull('date_fin_session'));
    }

    public function scopePubliables($query, $idSession){
        return $query->where('id_session',$idSession)->whereNull('date_publication');
    }

    /**
     * Le statut se déduit : il n'est pas stocké. Pour compter en SQL, utiliser
     * statutSql() ; les deux doivent rester identiques (un test le vérifie).
     */
    public function getStatutAttribute(): StatutAttributionEnum
    {
        $this->loadMissing('session');

        return match (true) {
            $this->date_destockage !== null => StatutAttributionEnum::retiree,
            $this->date_publication !== null => StatutAttributionEnum::publiee,
            $this->session?->estFinie() => StatutAttributionEnum::validee,
            default => StatutAttributionEnum::enSeance,
        };
    }

    /** Le même statut sous forme d'expression SQL (valeur entière de StatutAttributionEnum). */
    public static function statutSql(): string
    {
        return sprintf(
            'CASE
                WHEN attributions.date_destockage IS NOT NULL THEN %d
                WHEN attributions.date_publication IS NOT NULL THEN %d
                WHEN (SELECT sc.date_fin_session FROM sessions_commissions sc WHERE sc.id_session = attributions.id_session) IS NOT NULL THEN %d
                ELSE %d
            END',
            StatutAttributionEnum::retiree->value,
            StatutAttributionEnum::publiee->value,
            StatutAttributionEnum::validee->value,
            StatutAttributionEnum::enSeance->value,
        );
    }

    /**
     * Modifiable ou annulable tant que la séance est ouverte : la clôture de la
     * séance vaut validation, après quoi l'attribution ne bouge plus.
     */
    public function estModifiable(): bool
    {
        $this->loadMissing('session');

        return $this->session?->estOuverte() ?? false;
    }

    /** Le bon n'est imprimable qu'une fois les résultats de la séance publiés. */
    public function estImprimable(): bool
    {
        return $this->estPubliee();
    }
}
