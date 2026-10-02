<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Enums\StatutDemandeE;
use App\Observers\DemandeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 *  @property boolean $terrain_visite
 *  @property string $observations
 */
#[ObservedBy([DemandeObserver::class])]
class Demande extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_demande';

    protected $fillable = [
        'id_acteur',
        'id_type_acteur',
        'date_demande',
        'nom_structure',

        'periode_debut_mise_en_place',
        'periode_fin_mise_en_place',

        'date_soummission',
        'observations',
        'terrain_visite',

        'id_user',
        'id_campagne',

        'id_session',
        'motifs_rejet'
    ];

    protected $casts = [
        'date_demande' => 'date',
        'date_soummission' => 'datetime',

        'periode_debut_mise_en_place' => 'date',
        'periode_fin_mise_en_place' => 'date',

    ];

    /**
     * Exécuter les événements d'observer uniquement après le COMMIT de la transaction BDD.
     */
    public $afterCommit = true;

    // ---------------------------------------------------------------- Relations

    public function campagne(): BelongsTo {
        return $this->belongsTo(Campagne::class,'id_campagne','id_campagne');
    }
    public function acteur(): BelongsTo
    {
        return $this->belongsTo(Acteur::class, 'id_acteur', 'id_acteur');
    }

    public function typeActeur(): BelongsTo
    {
        return $this->belongsTo(TypeActeur::class, 'id_type_acteur', 'id_type_acteur');
    }

    public function localites(): BelongsToMany
{
    return $this->belongsToMany(
        Localite::class, 
        'demandes_localites', 
        'id_demande', 
        'id_localite'
    );
}
    
    public function sessionCommission() : HasOne {
        return $this->hasOne(SessionCommission::class, 'id_session','id_session');
    }

    /** Les superficies déclarées, ventilées par système de production. */
    public function systemesProductions(): HasMany
    {
        return $this->hasMany(SystemeProduction::class, 'id_demande', 'id_demande');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneDemande::class, 'id_demande', 'id_demande');
    }

    public function piecesDemandes(): HasMany
    {
        return $this->hasMany(PieceDemande::class, 'id_demande', 'id_demande');
    }

    public function attribution(): HasOne
    {
        return $this->hasOne(Attribution::class, 'id_demande', 'id_demande');
    }

    public function user(): BelongsTo
    {

        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function traitements()
    {
        return $this->hasMany(Traitement::class, 'id_demande', 'id_demande');
    }

    public function attributions() : HasMany {
        return $this->hasMany(Attribution::class,'id_demande','id_demande');
    }

    // ------------------------------------------------------------------ Métier

    /**
     * Les services qui doivent instruire la demande. Ils se déduisent des
     * appuis sollicités — un appui relève d'un type d'appui, lequel est offert
     * par un service — donc aucune table de routage n'est nécessaire.
     *
     * @return Collection<int, Service>
     */
    public function servicesConcernes(): Collection
    {
        $this->loadMissing('lignes.typeAppui.service');

        return $this->lignes
            ->map(fn (LigneDemande $ligne) => $ligne->typeAppui?->service)
            ->filter()
            ->unique('id_service')
            ->values();
    }

/**
     * Les types d'appuis contenus dans la demande
     * 
     * @return Collection<int, TypeAppui>
     */
    public function typesAppuis(): Collection
    {
        $this->loadMissing('lignes.typeAppui');

        return $this->lignes
            ->pluck('typeAppui')
            ->filter()
            ->unique('id_type_appui')
            ->values();
    }

    /**
     * Retourne les lignes d'un service donné
     */
    public function lignesService($idService)
    {
        return $this->lignes()->typeAppui()->service()
            ->where('id_service', $idService);
    }

    public function estBrouillon(): bool
    {
        return is_null($this->date_soummission);
    }

    public function estSoumise(): bool
    {
        return ! is_null($this->date_soummission);
    }

    public function soumettre(){
        $this->date_soummission = now();
        $this->save();
    }

    // ------------------------------------------------------------------ Scopes

    /**
     * 
     * @param User $user
     * @return void
     */
    public function scopePourUtilisateur($query)
    {
        $user = auth()->user();
        
        if ($user->hasRole(RoleEnum::acteur->value)) {
            return $query->whereHas('acteur', fn ($q) => $q->where('user_id', $user->id));
        } else if($user->hasRole([RoleEnum::agentService->value])){
            // « date_soummission » appartient à la demande, pas au service : le filtre se pose hors de la sous-requête.
            return $query->whereNotNull('date_soummission')
                ->whereHas('lignes.typeAppui.service', fn ($q) => $q->where('id_service', $user->id_service));
        } else if($user->hasRole(RoleEnum::agentCommission->value)){
            return $query->whereNotNull('date_soummission');
        } else if($user->hasRole(RoleEnum::gestionnaireStock->value)){
            return $query->where('id_user', $user->id);
        }
        // Agents internes et administrateurs voient l'ensemble des dossiers.
        return $query;
    }

    public function scopeAttribuables($query)
    {
        $user = auth()->user();

        if ($user->hasRole(RoleEnum::agentCommission->value)) {
            return $query->whereNotNull('date_soummission')
                            ->whereNull('id_session')
                            ->whereDoesntHave('attribution');
        }

        return $query;
    }

    public function estModifiable(): bool
    {
        return is_null($this->date_soummission);
    }

    /**
     * Récupère les services uniques concernés par cette demande.
     * Note : Le type de retour doit être Collection (de Laravel) car il y a plusieurs services.
     */
    public function getServices(): Collection
    {
        // 1. On charge toutes les relations d'un coup (Eager Loading)
        $this->load('lignes.typeAppui.service');

        // 2. On utilise la puissance des Collections Laravel pour extraire les services uniques
        return $this->lignes
            ->map(function ($ligne) {
                return $ligne->typeAppui?->service;
            })
            ->filter()    // Élimine les valeurs nulles si une relation est cassée
            ->unique('id_service') // Supprime les doublons basés sur l'ID du service
            ->values();   // Réinitialise les clés du tableau
    }

    public function aAttributions(){
        return count($this->attributions()->get()) > 0;
    }

    // public function estFavorablePourService($idService): bool {
    //     return $this->traitements()
    //     ->where('id_service', $idService)
    //     ->favorable()
    //     ->exists();
    // }

    public function demandeEnCours($idActeur){
        if(self::whereNull('date_soumission')->where('id_acteur',$idActeur)->exists()) return true;
    }

    /**
     * Rejeter une demande
     */
    public function rejeter($idSession, $motifsRejet) {
        $this->id_session = $idSession;
        $this->motifs_rejet = $motifsRejet;
        $this->save();
    }

    public function aRecuAttribution() {
        $this->motifs_rejet = null;
        $this->save();
    }

    // TERRAIN
    public function marquerVisite(string $observations) {
        $this->observations = $observations;
        $this->terrain_visite = true;
        $this->save();
    }

    public function terrainAEteVisite(){
        return $this->terrain_visite;
    }
}
