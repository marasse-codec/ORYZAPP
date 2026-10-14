<?php

namespace App\Domain\Journal\Models;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * Le journal des actions (SNF08) : qui a fait quoi, sur quoi, et quand.
 * Il ne se modifie ni ne se supprime : on y ajoute, c'est tout.
 */
class JournalAction extends Model
{
    use HasFactory;

    protected $table = 'journals_actions';

    protected $fillable = [
        'user_id',
        'action',
        'cible_type',
        'cible_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /** L'auteur de l'action. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** La chose sur laquelle l'action a porté (demande, attribution…), si elle existe encore. */
    public function cible(): MorphTo
    {
        return $this->morphTo();
    }

    /** L'action sous forme d'énumération, ou null pour un code inconnu. */
    public function actionEnum(): ?ActionJournalEnum
    {
        return ActionJournalEnum::tryFrom($this->action);
    }

    /**
     * Ajoute une entrée au journal, signée par l'utilisateur connecté.
     *
     * À appeler DANS la transaction de l'action qu'on journalise : l'action et sa
     * trace réussissent ou échouent ensemble. Hors session (seeders, console), il
     * n'y a pas d'auteur à nommer : rien n'est écrit.
     *
     * @param  array<string, mixed>  $details
     */
    public static function enregistrer(ActionJournalEnum $action, Model $cible, array $details = []): ?self
    {
        if (! Auth::check()) {
            return null;
        }

        return self::create([
            'user_id' => Auth::id(),
            'action' => $action->value,
            'cible_type' => $cible->getMorphClass(),
            'cible_id' => $cible->getKey(),
            'details' => $details ?: null,
        ]);
    }
}
