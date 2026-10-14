<?php

namespace App\Domain\Commission\Models;

use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionCommission extends Model
{
    protected $table = 'sessions_commissions';

    protected $primaryKey = 'id_session';

    protected $fillable = ['resume', 'date_debut_session', 'date_fin_session'];

    protected $casts = [
        'date_debut_session' => 'date',
        'date_fin_session' => 'date',
    ];

    public function demandes() : HasMany {
        return $this->hasMany(Demande::class,'id_session','id_session');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'id_session', 'id_session');
    }

    /** Tant que la séance n'est pas clôturée, elle peut recevoir des dossiers. */
    public function estOuverte(): bool
    {
        return $this->date_fin_session === null;
    }

    public function estFinie(): bool {
        return ! is_null($this->date_fin_session);
    }

    /** 
     * Une séance est publiable si elle est clôturée 
     * et possède au moins une attribution non encore publiée.
     */
    public function estPubliable(): bool
    {
        return $this->estFinie() 
            && Attribution::publiables($this->id_session)->exists();
    }

    /** @return int nombre d'attributions publiées (0 : il n'y avait rien à publier) */
    public function publierAttributions(): int
    {
        return DB::transaction(function () {
            // Chargement uniquement des attributions non encore publiées
            /** @var Collection<Attribution> $attributionsAPublier */
            $attributionsAPublier = $this->attributions()
                ->whereNull('date_publication')
                ->get();

            foreach ($attributionsAPublier as $attrib) {
                $attrib->publier();

                JournalAction::enregistrer(ActionJournalEnum::attributionPubliee, $attrib, ['id_session' => $this->id_session]);
            }

            return $attributionsAPublier->count();
        });
    }
}
