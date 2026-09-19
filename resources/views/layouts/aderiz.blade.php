@php
    use App\Enums\PermissionEnum;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    @livewireStyles
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varyza - Systeme de Gestion des appuis ADERIZ</title>
    
    {{-- Balises PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#047857">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    {{-- Livewire & Filament Styles --}}
    @livewireStyles
    @filamentStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 h-screen lg:flex">

    <aside class="lg:w-64 bg-[--color-primary] text-white flex flex-col">
        <div class="p-6 text-sm font-bold border-b border-slate-700">ADERIZ GESTION APPUIS</div>

        <a href="{{ route('dashboard') }}"
           class="flex items-center border-b border-slate-700 w-full p-3 text-white hover:bg-slate-700">
            Tableau de bord
        </a>

        {{-- Demandes --}}
        <x-menu-groupe titre="Demandes">
            @can(PermissionEnum::creerDemande->value)
                <a href="{{ route('demandes.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Nouvelle demande</a>
            @endcan
            <a href="{{ route('demandes.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les demandes</a>
        </x-menu-groupe>

        @can(PermissionEnum::instruireDemande->value)
            <x-menu-groupe titre="Instruction">
                <a href="{{ route('instructions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Demandes à instruire</a>
            </x-menu-groupe>
        @endcan

        @can(PermissionEnum::gererCommission->value)
            <x-menu-groupe titre="Commissions">
                <a href="{{ route('commissions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les sessions</a>
                <a href="{{ route('commissions.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Ouvrir une session</a>
                <a href="{{ route('commissions.synthese') }}" target="_blank" class="block p-2 text-sm text-gray-300 hover:text-white">Synthèse à imprimer</a>
            </x-menu-groupe>
        @endcan

        <x-menu-groupe titre="Attributions">
            <a href="{{ route('attributions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les attributions</a>
            @can(PermissionEnum::attribuerAppui->value)
                <a href="{{ route('attributions.simulation') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Simuler les sorties</a>
                <a href="{{ route('attributions.createDirect') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Attribution Directe</a>
            @endcan
        </x-menu-groupe>

        @can(PermissionEnum::gererStock->value)
            <x-menu-groupe titre="Stocks">
                <a href="{{ route('appuis.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Catalogue des appuis</a>
                <a href="{{ route('appuis.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Nouvel appui</a>
                <a href="{{ route('approvisionnements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Approvisionnements</a>
                <a href="{{ route('recuperations.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Sorties de stock</a>
                <a href="{{ route('equipements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Gestion des Équipements</a>
                
            </x-menu-groupe>
        @endcan

        <x-menu-groupe titre="Équipement">
            <a href="{{ route('equipements.retour.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Retour d'équipement</a>
        </x-menu-groupe>

        <x-menu-groupe titre="Mouvements">
            <a href="{{ route('mouvements-stocks.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Mouvements</a>
        </x-menu-groupe>

        <x-menu-groupe titre="Ressources">
            <a href="/ressources" class="block p-2 text-sm text-gray-300 hover:text-white">Accéder aux ressources</a>
        </x-menu-groupe>

        <div class="mt-auto w-full p-4 border-t border-slate-700">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-red-400 text-sm hover:underline">Se déconnecter</button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex h-16 bg-white border-b items-center justify-between px-8">
            <span class="font-semibold text-gray-700">Bienvenue {{ auth()->user()->name }}</span>

            {{-- Zone des Notifications & Action Push --}}
            <div class="flex items-center gap-4">
                
                {{-- Bouton d'activation des Push --}}
                <button id="btn-push-notif" 
                        onclick="enablePushNotifications()" 
                        class="hidden text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-medium px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">notifications_active</span>
                    Activer les notifs
                </button>

                {{-- Composant Cloche / Notifications In-App --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="relative p-2 text-slate-600 hover:text-emerald-700 focus:outline-none">
                        <span class="material-symbols-outlined text-2xl">notifications</span>
                        @if (auth()->check() && auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-xl z-50">
                        <div class="flex items-center justify-between border-b pb-2 mb-2">
                            <h3 class="text-sm font-bold text-slate-800">Notifications</h3>
                            <span class="text-xs text-slate-400">Varyza</span>
                        </div>

                        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100">
                            @if(auth()->check())
                                @forelse (auth()->user()->unreadNotifications as $notification)
                                    <div class="py-2 text-xs">
                                        <p class="font-semibold text-slate-800">{{ $notification->data['titre'] ?? 'Notification' }}</p>
                                        <p class="text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                                        <div class="mt-1 flex justify-between items-center">
                                            <span class="text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                            @if(!empty($notification->data['url']))
                                                <a href="{{ $notification->data['url'] }}" class="text-emerald-700 font-semibold hover:underline">Voir</a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 py-4 text-center">Aucune nouvelle notification</p>
                                @endforelse
                            @endif
                        </div>
                    </div>
                </div>

                <div class="text-sm text-gray-500 border-l pl-4">Varyza</div>
            </div>
        </header>

        <main class="flex-1 bg-[--color-secondary] overflow-y-auto">
            @yield('content')
        </main>
    </div>

    {{-- Composant Modal Plein Écran --}}
    <div x-data="fullScreenAlertManager()" 
        x-show="showAlert" 
        x-cloak
        class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-red-950 p-6 transition-all duration-300">
        
        <div class="text-center max-w-xl mx-auto space-y-6 bg-red-900 p-8 rounded-2xl shadow-2xl border border-red-700">
            {{-- Icône d'Alerte Animée --}}
            <div class="inline-flex p-4 rounded-full bg-red-600 text-white animate-bounce shadow-lg">
                <span class="material-symbols-outlined text-6xl">warning</span>
            </div>

            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white" x-text="alertData.title"></h1>
            
            <p class="text-lg text-red-100 font-medium" x-text="alertData.body"></p>

            <div class="pt-6 flex flex-col sm:flex-row gap-4 justify-center items-center">
                <template x-if="alertData.url">
                    <a :href="alertData.url" 
                    class="w-full sm:w-auto px-8 py-3.5 bg-white text-red-900 font-extrabold rounded-xl shadow-lg hover:bg-red-50 transition-colors text-center block">
                        Consulter immédiatement
                    </a>
                </template>
                
                <button @click="closeAlert()" 
                        type="button"
                        class="w-full sm:w-auto px-6 py-3.5 bg-red-950 text-white font-bold rounded-xl border border-red-700 hover:bg-red-800 transition-colors shadow-md">
                    Fermer l'alerte
                </button>
            </div>
        </div>
    </div>

    {{-- Scripts Livewire & Filament --}}
    @livewireScripts
    @filamentScripts
    @livewire('notifications')

    {{-- Configuration PWA & Push Notifications --}}
    <script>
        const VAPID_PUBLIC_KEY = "{{ config('webpush.vapid.public_key') }}";

        // Enregistrement du Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('Service Worker Varyza enregistré.'))
                .catch(err => console.error('Erreur Service Worker:', err));
        }

        // Gestion automatique et affichage du bouton
        document.addEventListener("DOMContentLoaded", function () {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) return;

            if (Notification.permission === 'granted') {
                // Si la permission est accordée, on s'assure que le jeton est synchronisé avec Laravel
                navigator.serviceWorker.ready.then(registration => {
                    registration.pushManager.getSubscription().then(subscription => {
                        if (!subscription) {
                            // Permission accordée mais jeton manquant -> On souscrit automatiquement
                            enablePushNotifications(false);
                        }
                    });
                });
            } else if (Notification.permission === 'default') {
                // Permission pas encore demandée -> On affiche le bouton
                const btn = document.getElementById('btn-push-notif');
                if (btn) btn.classList.remove('hidden');
            }
        });

        function checkPushSubscriptionStatus() {
            // 1. Vérifier si les notifications sont supportées
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                console.log("Les notifications Push ne sont pas supportées.");
                return;
            }

            // 2. Vérifier l'état de la permission
            if (Notification.permission === 'granted') {
                // La permission est accordée, vérifions si l'appareil a un abonnement actif
                navigator.serviceWorker.ready.then(registration => {
                    registration.pushManager.getSubscription().then(subscription => {
                        if (subscription) {
                            console.log("L'appareil est déjà abonné aux notifications Push :", subscription);
                            // L'appareil a DÉJÀ accepté et est ABOUNÉ
                            // Ex: Vous pouvez cacher le bouton ou afficher un badge "Notifications activées"
                            const btn = document.getElementById('btn-push-notif');
                            if (btn) btn.classList.add('hidden');
                        } else {
                            console.log("Permission accordée, mais aucun abonnement Push actif. Réabonnement nécessaire.");
                            // On peut ré-enregistrer automatiquement l'abonnement auprès du serveur
                            enablePushNotifications();
                        }
                    });
                });

            } else if (Notification.permission === 'default') {
                console.log("L'utilisateur n'a pas encore fait de choix.");
                // Afficher le bouton pour lui proposer l'activation
                const btn = document.getElementById('btn-push-notif');
                if (btn) btn.classList.remove('hidden');

            } else if (Notification.permission === 'denied') {
                console.warn("L'utilisateur a bloqué les notifications sur ce navigateur.");
                // Le bouton reste caché car la navigateur bloquera toute nouvelle demande
            }
        }

        function fullScreenAlertManager() {
            return {
                showAlert: false,
                alertData: { title: '', body: '', url: '' },
                init() {
                    // Écoute des messages envoyés par le Service Worker
                    const alertChannel = new BroadcastChannel('varyza_alerts');
                    alertChannel.onmessage = (event) => {
                        if (event.data && event.data.type === 'FULLSCREEN_ALERT') {
                            this.triggerFullScreen(event.data.payload);
                        }
                    };
                },
                triggerFullScreen(payload) {
                    this.alertData = {
                        title: payload.title || 'ALERTE URGENTE',
                        body: payload.body || '',
                        url: payload.data?.url || '#'
                    };
                    this.showAlert = true;

                    // Passer l'application en mode plein écran natif navigateur (si autorisé)
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    }
                },
                closeAlert() {
                    this.showAlert = false;
                    if (document.fullscreenElement) {
                        document.exitFullscreen().catch(() => {});
                    }
                }
            }
        }

        // Utilitaire de conversion pour VAPID
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
        }

        // Action du bouton d'activation Push
        function enablePushNotifications() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                alert('Les notifications Push ne sont pas supportées par ce navigateur.');
                return;
            }

            navigator.serviceWorker.ready.then(registration => {
                registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                })
                .then(subscription => {
                    fetch("{{ route('push.subscribe') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(subscription)
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert('Notifications Push activées sur cet appareil !');
                        document.getElementById('btn-push-notif').classList.add('hidden');
                    });
                })
                .catch(err => console.error('Erreur souscription Push:', err));
            });
        }
    </script>
</body>
</html>