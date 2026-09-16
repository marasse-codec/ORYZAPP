<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Enums\StatutDemandeE;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Demande extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_demande';

    protected $fillable = [
        'id_acteur',
        'id_type_acteur',
        'id_localite',
        'date_demande',

        'periode_debut_mise_en_place',
        'periode_fin_mise_en_place',

        'soumise',

        'id_user',
        'id_campagne'
    ];

    protected $casts = [
        'date_demande' => 'date',

        'periode_debut_mise_en_place' => 'date',
        'periode_fin_mise_en_place' => 'date',

    ];

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

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class, 'id_localite', 'id_localite');
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

    /** L'instruction de la demande, un rapport par service concerné. */
    public function rapports(): HasMany
    {
        return $this->hasMany(Rapport::class, 'id_demande', 'id_demande');
    }

    public function attribution(): HasMany
    {
        return $this->hasMany(Attribution::class, 'id_demande', 'id_demande');
    }

    public function user(): BelongsTo
    {

        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function traitements()
    {
        return $this->hasMany(Traitement::class, 'id_demande', 'id_demande');
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
        return $this->id_statut_demande === StatutDemandeE::brouillon;
    }

    public function estSoumise(): bool
    {
        return $this->id_statut_demande === StatutDemandeE::soumise;
    }

    public function soumettre(){
        $this->soumise = true;
        $this->save();
    }

    public function estACorriger(): bool
    {
        return $this->id_statut_demande === StatutDemandeE::a_corriger;
    }

    /**
     * Rejeter une demande d'appui
     */
    public function rejeter(string $motif,array $services) {


        $lignesConcernees = $this->lignes() // Adaptez le nom de la relation si nécessaire (ex: 'lignes')
                ->whereHas('typeAppui', function ($query) use ($services) {
                    $query->whereIn('id_service', $services); 
                })
                ->get();

        foreach ($lignesConcernees as $ligne) {
            $ligne->rejeterLigne($motif);
        }

        // 2. On compte s'il reste des lignes non rejetées pour CETTE demande
        $lignesNonRejeteesRestantes = $this->lignes()
            ->whereNull('date_rejet_service') // S'assure qu'il n'en reste aucune autre
            ->count();

        if ($lignesNonRejeteesRestantes === 0) {
            $this->id_statut_demande = StatutDemandeE::rejetee;
            $this->save();
        }

    }

    public function renvoyerPourCorrection(
        Traitement $traitement,
        string $motif
    ): DemandeCorrection {
        if (! $this->estSoumise()) {
            throw new \LogicException(
                'Seule une demande soumise peut être renvoyée pour correction.'
            );
        }

        $this->update([
            'id_statut_demande' => StatutDemandeE::a_corriger,
        ]);

        return $traitement->demandesCorrections()->create([
            'motif' => $motif,
        ]);
    }

    public function traitementTerminee()
    {
        $this->update([
            'id_statut_demande' => StatutDemandeE::recu_attribution,
        ]);
    }

    // ------------------------------------------------------------------ Scopes

    /**
     * 
     * @param User $user
     * @return void
     */
    public function scopePourUtilisateur($query, $user)
    {
        if ($user->hasRole(RoleEnum::acteur->value)) {
            return $query->whereHas('acteur', fn ($q) => $q->where('user_id', $user->id));
        } else if($user->hasRole([RoleEnum::agentService->value])){
            return $query->whereHas('lignes.typeAppui.service', function ($q) use ($user) {
                $q->where('id_service',$user->id_service)->where('soumise',true);
            });
        } else if($user->hasRole(RoleEnum::agentCommission->value)){
            return $query->whereHas('lignes.typeAppui.service', function ($q) use ($user) {
                $q->where('soumise',true);
            });
        }
        // Agents internes et administrateurs voient l'ensemble des dossiers.
        return $query;
    }

    public function estModifiable(): bool
    {
        return !$this->soumise;
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
}
