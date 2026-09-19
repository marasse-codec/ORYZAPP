<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class RessourceAlerteNotification extends Notification
{
    public function __construct(
        public $ressource, 
        public string $titre, 
        public string $message,
        public array $channels = ['email','database', WebPushChannel::class]
    ) {}

    public function via($notifiable): array
    {
        return $this->channels;
    }

    /**
     * 📧 Construction de l'e-mail
     */
    public function toMail($notifiable): MailMessage // 👈 2. Ajouter cette méthode
    {
        // Récupération du nom (prend $notifiable->nom pour un Acteur ou $notifiable->name pour un User)
        $nom = $notifiable->nom ?? $notifiable->name ?? 'Cher destinataire';

        return (new MailMessage)
            ->subject($this->titre)
            ->greeting("Bonjour {$nom},")
            ->line($this->message)
            ->action('Consulter le détail', $this->url ?? url('/'))
            ->line('Merci d\'utiliser notre plateforme.');
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->titre)
            ->icon('/images/icon-192x192.png')
            ->body($this->message)
            ->action('Voir la ressource', 'open_ressource')
            ->data(['url' => route('appuis.index', $this->ressource->id_appui)]);
    }

    public function toArray($notifiable): array
    {
        return [
            'titre' => $this->titre,
            'message' => $this->message,
            'url' => route('appuis.index', $this->ressource->id_appui),
        ];
    }
}