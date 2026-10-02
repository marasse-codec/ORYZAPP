<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SuiviDossierNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $titre,
        public string $message,
        public string $urlAction = '',
        public string $type = 'info', // info, warning, success, danger,
        public bool $estUrgente = false, // Nouveau drapeau d'urgence
        public array $canaux = ['database', WebPushChannel::class] // Canaux par défaut
    ) {}

    /**
     * Canaux de diffusion :
     * - 'database'     : Cloche / In-App (table `notifications`)
     * - WebPushChannel : PWA Push sur smartphone
     * - 'mail'         : Email Out-App
     */
    public function via(object $notifiable): array
    {
        return $this->canaux;
    }

    /**
     * 1. Canal In-App (Base de données)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'titre' => $this->titre,
            'message' => $this->message,
            'url' => $this->urlAction,
            'type' => $this->type,
        ];
    }

    /**
     * 2. Canal PWA (Notification Push)
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->titre)
            ->body($this->message)
            ->icon('/images/icons/icon-192x192.png')
            ->badge('/images/icons/icon-192x192.png')
            ->data([
                'url' => $this->urlAction,
                'type' => $this->type,
                'fullscreen' => $this->estUrgente // Transmis au JS
                ])
            ->options(['TTL' => 1000]);
    }

    /**
     * 3. Canal Email
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Varyza / ADERIZ : ' . $this->titre)
            ->greeting('Bonjour ' . ($notifiable->nom ?? ''))
            ->line($this->message)
            ->action('Consulter le dossier', $this->urlAction ?: url('/'))
            ->line('Merci d\'utiliser la plateforme Varyza.');
    }
}