<?php

namespace App\Observers;

use App\Models\Demande;
use App\Models\User;
use App\Notifications\SuiviDossierNotification;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class DemandeObserver
{
    /**
     * Déclenché automatiquement APRÈS la création ($demande->save())
     */
    public function created(Demande $demande): void
    {

        // 1. Vérifier si l'observer est bien appelé
        \Illuminate\Support\Facades\Log::info("Observer déclenché pour demande #{$demande->id_demande} - User ID: " . ($demande->user?->id ?? 'AUCUN'));
        // 2. Vérifier si l'utilisateur est bien trouvé
        if (! $demande->user) {
            \Illuminate\Support\Facades\Log::error("Impossible de notifier : L'utilisateur associé à id_user ({$demande->id_user}) est introuvable !");
            return;
        }
        
        // Notifier le demandeur que son dossier est bien reçu
        $demande->user?->notify(new SuiviDossierNotification(
            titre: 'Demande enregistrée',
            message: "Votre demande d'appui a bien été créé mais pas soumise et porte le numéro #{$demande->id_demande}.",
            urlAction: route('demandes.index', $demande->id_demande),
            type: 'success',
            estUrgente: false,
            canaux: ['database',WebPushChannel::class]
        ));
    }

    /**
     * Déclenché automatiquement APRÈS une mise à jour ($demande->update())
     */
    public function updated(Demande $demande): void
    {
        // wasChanged() vérifie si la colonne 'statut' a été modifiée lors de cet update
        if ($demande->wasChanged('date_soummission')) {
            $demande->user?->notify(new SuiviDossierNotification(
                titre: 'Dossier soumis !',
                message: "Votre demande #{$demande->id_demande} a été soumise.",
                urlAction: route('demandes.show', $demande->id_demande),
                type: 'success',
                estUrgente: false,
                canaux: ['database',WebPushChannel::class]
            ));
            
            $servicesIds = $demande->getServices()->pluck('id_service')->filter()->toArray();

            if (!empty($servicesIds)) {
                // On recherche tous les utilisateurs rattachés à ces services
                $agents = User::whereIn('id_service', $servicesIds)->get();

                if ($agents->isNotEmpty()) {
                    Notification::send($agents, new SuiviDossierNotification(
                        titre: 'Nouvelle demande pour votre service',
                        message: "La demande #{$demande->id_demande} requiert l'attention de votre service.",
                        urlAction: route('demandes.index'),
                        type: 'info',
                        estUrgente: true,
                        canaux: ['database', WebPushChannel::class]
                    ));
                }
            }
        };
    }

    /**
     * Handle the Demande "deleted" event.
     */
    public function deleted(Demande $demande): void
    {
        //
    }

    /**
     * Handle the Demande "restored" event.
     */
    public function restored(Demande $demande): void
    {
        //
    }

    /**
     * Handle the Demande "force deleted" event.
     */
    public function forceDeleted(Demande $demande): void
    {
        //
    }
}
