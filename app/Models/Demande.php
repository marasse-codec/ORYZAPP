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
        'debut_periode_mise_en_place',
        'date_prise_en_charge',
        'en_cours_de_correction',
        'motifs_rejet',
        'date_rejet',
        'date_attribution',
        'id_statut_demande',
        'id_user',
        'id_campagne'
    ];

    protected $casts = [
        'date_demande' => 'date',
        'debut_periode_mise_en_place' => 'date',
        'date_prise_en_charge' => 'datetime',
        'date_rejet' => 'date',
        'date_attribution' => 'date',
        'id_statut_demande' => StatutDemandeE::class,
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
    public function systemesProduction(): HasMany
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

    public function attribution(): HasOne
    {
        return $this->hasOne(Attribution::class, 'id_demande', 'id_demande');
    }

    public function user(): BelongsTo
    {

        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function demandesCorrections(): HasMany
    {
        return $this->hasMany(
            DemandeCorrection::class,
            'id_demande',
            'id_demande'
        );
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
        $this->loadMissing('lignes.appui.typeAppui.service');

        return $this->lignes
            ->map(fn (LigneDemande $ligne) => $ligne->appui?->typeAppui?->service)
            ->filter()
            ->unique('id_service')
            ->values();
    }

    /**
     * Retourne les lignes d'un service donné
     */
    public function lignesService($idService)
    {
        return $this->lignes()->appui()->typeAppui()->service()
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

    public function estACorriger(): bool
    {
        return $this->id_statut_demande === StatutDemandeE::a_corriger;
    }

    /**
     * Rejeter une demande d'appui
     */
    public function rejeter(string $motif,array $services) {


        $lignesConcernees = $this->lignes() // Adaptez le nom de la relation si nécessaire (ex: 'lignes')
                ->whereHas('appui.typeAppui', function ($query) use ($services) {
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

    public function scopePourUtilisateur($query, $user)
    {
        if ($user->hasRole(RoleEnum::acteur->value)) {
            return $query->whereHas('acteur', fn ($q) => $q->where('user_id', $user->id));
        } else if($user->hasRole(RoleEnum::agentService->value)){
            return $query->whereHas('lignes.appui.typeAppui.service', function ($q) use ($user) {
                $q->where('id_service',$user->id_service);
            });
        } else if($user->hasRole(RoleEnum::agentCommission->value)){
            return $query->where('id_statut_demande',StatutDemandeE::soumise);
        }
        // Agents internes et administrateurs voient l'ensemble des dossiers.
        return $query;
    }

    public function estModifiable(): bool
    {
        return in_array(
            $this->id_statut_demande,
            [
                StatutDemandeE::brouillon,
                StatutDemandeE::a_corriger,
            ],
            true
        );
    }

    /**
     * Récupère les services uniques concernés par cette demande.
     * Note : Le type de retour doit être Collection (de Laravel) car il y a plusieurs services.
     */
    public function getServices(): Collection
    {
        // 1. On charge toutes les relations d'un coup (Eager Loading)
        $this->load('lignes.appui.typeAppui.service');

        // 2. On utilise la puissance des Collections Laravel pour extraire les services uniques
        return $this->lignes
            ->map(function ($ligne) {
                return $ligne->appui?->typeAppui?->service;
            })
            ->filter()    // Élimine les valeurs nulles si une relation est cassée
            ->unique('id_service') // Supprime les doublons basés sur l'ID du service
            ->values();   // Réinitialise les clés du tableau
    }
}
