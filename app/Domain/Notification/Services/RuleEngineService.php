<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationRule;
use App\Domain\Notification\Notifications\RessourceAlerteNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RuleEngineService
{
    /*private static function resolveRecipients($model, NotificationRule $rule): Collection
    {
        $users = collect();

        switch ($rule->recipient_type) {
            case 'subscribers':
                // 1. Récupère les abonnés via la relation polymorphe
                $users = $model->subscriptions()->with('user')->get()->pluck('user');
                break;

            case 'roles':
                // 2. Récupère tous les users possédant les rôles spécifiés (Spatie)
                $roles = $rule->recipient_targets ?? [];
                $users = User::role($roles)->get();
                break;

            case 'users':
                // 3. Récupère directement les users par leurs IDs
                $userIds = $rule->recipient_targets ?? [];
                $users = User::whereIn('id', $userIds)->get();
                break;

            case 'relation':
                // 4. Extrait dynamiquement l'utilisateur lié (ex: $model->user)
                $relationName = is_array($rule->recipient_targets) 
                    ? ($rule->recipient_targets[0] ?? null) 
                    : $rule->recipient_targets;

                if ($relationName && $model->{$relationName}) {
                    $users = collect([$model->{$relationName}]);
                }
                break;
        }

        // 5. Filtre les doublons et valide la Policy (Double verrou de sécurité)
        return $users->filter()->unique('id')->filter(function (User $user) use ($model) {
            return $user->can('view', $model);
        });
    }

    public static function dispatchNotifications($model, NotificationRule $rule): void
    {
        $recipients = self::resolveRecipients($model, $rule);

        foreach ($recipients as $user) {
            $user->notify(new \App\Domain\Notification\Notifications\RessourceAlerteNotification(
                $model,
                self::parseTemplate($rule->title_template, $model),
                self::parseTemplate($rule->message_template, $model)
            ));
        }
    }*/

    // Dans RuleEngineService.php

    // private static function resolveRecipients($model, NotificationRule $rule): Collection
    // {
    //     $users = collect();

    //     switch ($rule->recipient_type) {
    //         case 'relation':
    //             // Extrait la chaîne de relation (ex: "demande.user")
    //             $relationPath = is_array($rule->recipient_targets) 
    //                 ? ($rule->recipient_targets[0] ?? null) 
    //                 : $rule->recipient_targets;

    //             if ($relationPath) {
    //                 // data_get() évalue dynamiquement $attribution->demande->user
    //                 $targetUser = data_get($model, $relationPath);

    //                 if ($targetUser instanceof User) {
    //                     $users = collect([$targetUser]);
    //                 }
    //             }
    //             break;

    //         // ... cas 'roles', 'users', 'subscribers' ...
    //     }

        

    //     // Filtre de sécurité par la Policy
    //     return $users->filter()->unique('id')->filter(fn (User $user) => $user->can('view', $model));
    // }

    protected static function resolveRecipients(Model $model,NotificationRule $rule): Collection
    {
        $recipients = collect();

        // 1. Extraire les cibles de la règle (ex: 'demande.acteur')
        $targets = is_array($rule->recipient_targets) 
            ? $rule->recipient_targets 
            : [$rule->recipient_targets];

        foreach ($targets as $target) {
            $resolved = data_get($model, $target);

            if ($resolved instanceof Collection) {
                $recipients = $recipients->merge($resolved);
            } elseif ($resolved) {
                $recipients->push($resolved);
            }
        }

        // 2. Filtrer les destinataires selon leur type
        return $recipients->unique('email')->filter(function ($recipient) use ($model) {

            // 👉 SI C'EST UN ACTEUR (ou tout modèle externe non-User) :
            // On autorise directement l'envoi de la notification/email
            if (! ($recipient instanceof User)) {
                return true;
            }

            // 👉 SI C'EST UN USER DU BACK-OFFICE :
            // On vérifie la Policy pour s'assurer qu'il a le droit de voir l'enregistrement
            return $recipient->can('view', $model);
        });
    }

    public static function dispatchNotifications($model, NotificationRule $rule): void
    {
        $recipients = self::resolveRecipients($model, $rule);

        // Résolution des noms de classes de canaux
        $channels = collect($rule->channels)->map(function ($channel) {
            return match ($channel) {
                'webpush' => \NotificationChannels\WebPush\WebPushChannel::class,
                default => $channel, // 'mail', 'database'
            };
        })->toArray();

        foreach ($recipients as $user) {
            $user->notify(new RessourceAlerteNotification(
                $model,
                self::parseTemplate($rule->title_template, $model),
                self::parseTemplate($rule->message_template, $model),
                $channels // Transmission des canaux choisis par l'admin
            ));
        }
    }

    public static function evaluate($model, string $event): void
    {
        // 1. Récupère les règles correspondant au modèle et à l'événement
        $rules = NotificationRule::where('resource_type', get_class($model))
            ->where('event', $event)
            ->get();

        foreach ($rules as $rule) {
            if (self::passesCondition($model, $rule)) {
                self::dispatchNotifications($model, $rule);
            }
        }
    }

    private static function passesCondition($model, $rule): bool
    {
        if (!$rule->field) return true; // Règle inconditionnelle (ex: tout mouvement de stock)

        $currentValue = $model->{$rule->field};

        return match ($rule->operator) {
            'is_not_null' => ! is_null($currentValue),
            'changed'     => $model->wasChanged($rule->field),
            '='           => (string) $currentValue === (string) $rule->value,
            '<'           => $currentValue < $rule->value,
            '>'           => $currentValue > $rule->value,
            default       => true,
        };
    }

    /*private static function dispatchNotifications($model, $rule): void
    {
        // Envoi aux abonnés du modèle
        foreach ($model->subscriptions()->with('user')->get() as $sub) {
            $user = $sub->user;
            if ($user && $user->can('view', $model)) {
                $user->notify(new \App\Domain\Notification\Notifications\RessourceAlerteNotification(
                    $model,
                    self::parseTemplate($rule->title_template, $model),
                    self::parseTemplate($rule->message_template, $model)
                ));
            }
        }
    }*/

    private static function parseTemplate(string $template, $model): string
    {
        // Remplace {champ} par la valeur réelle du modèle
        return preg_replace_callback('/\{(\w+)\}/', fn($m) => $model->{$m[1]} ?? '', $template);
    }
}