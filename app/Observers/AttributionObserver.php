<?php

namespace App\Observers;

use App\Enums\RoleEnum;
use App\Models\Attribution;
use App\Models\User;
use App\Notifications\SuiviDossierNotification;
use App\Services\RuleEngineService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class AttributionObserver
{
    /**
     * Handle the Attribution "created" event.
     */
    public function created(Attribution $attribution): void
    {
        //
    }

    /**
     * Handle the Attribution "updated" event.
     */
    public function updated(Attribution $attribution): void
    {
        auth()->user()?->notify(new SuiviDossierNotification(
                        titre: 'L\'attribution a été soumise',
                        message: "L'attribution #{$attribution->id_attribution} a été soumise.",
                        urlAction: route('attributions.show',$attribution->id_attribution),
                        type: 'info',
                        estUrgente: true,
                        canaux: ['database', WebPushChannel::class]
                    ));
        
        //Log::info("Message de test");
        // wasChanged() vérifie si la colonne 'statut' a été modifiée lors de cet update
        if ($attribution->wasChanged('id_statut_attribution')) {
            
            $agentsCommissions = User::role(RoleEnum::agentCommission)->get();
            //Log::info($agentsCommissions);

            if (!empty($agentsCommissions)) {
                // On recherche tous les utilisateurs rattachés à ces services
            //Log::info("intered");

                Notification::send($agentsCommissions, new SuiviDossierNotification(
                    titre: 'Nouvelle attribution pour votre service',
                    message: "L'attribution #{$attribution->id_attribution} requiert votre attention.",
                    urlAction: route('attributions.show',$attribution->id_attribution),
                    type: 'info',
                    estUrgente: true,
                    canaux: ['database', WebPushChannel::class]
                ));
            }
        };
    }

    /**
     * Handle the Attribution "deleted" event.
     */
    public function deleted(Attribution $attribution): void
    {
        //
    }

    /**
     * Handle the Attribution "restored" event.
     */
    public function restored(Attribution $attribution): void
    {
        //
    }

    /**
     * Handle the Attribution "force deleted" event.
     */
    public function forceDeleted(Attribution $attribution): void
    {
        //
    }
}
