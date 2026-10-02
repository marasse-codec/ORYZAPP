@php
    use App\Enums\PermissionEnum;
    use Illuminate\Support\Facades\Gate;
    use App\Enums\RoleEnum;
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    @livewireStyles
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oryzapp - Systeme de Gestion des appuis ADERIZ</title>
    
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
{{-- Remplacez votre balise <body> par ceci : --}}
<body class="fi-body fi-panel-admin bg-gray-100 h-screen lg:flex">
    <aside class="lg:w-45 bg-[--color-primary] text-white flex flex-col">
        <a href="{{config('app.backoffice_dashboard_url')}}" class="p-6 justify-center text-lg font-bold border-b border-slate-700">
            <p>{{config('app.name')}} </p>
            <p>Gestion des appuis</p>
        </a>

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

        @can(PermissionEnum::gererCommission->value)
            <x-menu-groupe titre="Commissions">
                <a href="{{ route('commissions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les sessions</a>
                {{-- <a href="{{ route('commissions.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Ouvrir une session</a> --}}
                {{-- <a href="{{ route('commissions.synthese') }}" target="_blank" class="block p-2 text-sm text-gray-300 hover:text-white">Synthèse à imprimer</a> --}}
            </x-menu-groupe>
        @endcan

        {{--Attributions--}}
        <x-menu-groupe titre="Attributions">
            <a href="{{ route('attributions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les attributions</a>

            @can(PermissionEnum::attribuerAppui->value)

                @can(PermissionEnum::gererSimulation->value)

                    <a href="{{ route('attributions.simulation') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Simuler les sorties</a>

                @endcan

                {{-- <a href="{{ route('attributions.createDirect') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Attribution Directe</a> --}}
            @endcan
        </x-menu-groupe>

        {{--Mouvements--}}
        @can(PermissionEnum::gererStock->value)
            <x-menu-groupe titre="Mouvements">
                <a href="{{ route('approvisionnements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Approvisionnements</a>
                <a href="{{ route('enlevements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Enlèvements</a>
                <a href="{{ route('equipements.retour.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Restitutions</a>
            </x-menu-groupe>
        @endcan

        @can(PermissionEnum::gererStock->value)
            <x-menu-groupe titre="Equipements">
        <a href="{{ route('equipements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Equipements</a>
            </x-menu-groupe>
        @endcan

        @hasrole(RoleEnum::admin->value)
            <x-menu-groupe titre="Ressources">
                <a href="/ressources" class="block p-2 text-sm text-gray-300 hover:text-white">Accéder aux ressources</a>
            </x-menu-groupe>
        @endhasrole

        {{--Editions--}}
        <x-menu-groupe titre="Editions">
            <a href="{{ route('editions.attributions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Demandes</a>
            <a href="{{ route('editions.attributions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Attributions</a>
            <a href="{{ route('editions.arbitrages.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Arbitrages</a>
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
                            <span class="text-xs text-slate-400">Oryzapp</span>
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

                <div class="text-sm text-gray-500 border-l pl-4">Oryzapp</div>
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

    // 1. Enregistrement du Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(() => console.log('Service Worker Oryzapp enregistré.'))
            .catch(err => console.error('Erreur Service Worker:', err));
    }

    // 2. Initialisation au chargement de la page
    document.addEventListener("DOMContentLoaded", function () {
        checkAndSyncPushSubscription();
    });

    function checkAndSyncPushSubscription() {
        if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn("Les notifications Push ne sont pas supportées.");
            return;
        }

        const btn = document.getElementById('btn-push-notif');

        if (Notification.permission === 'granted') {
            // Cacher le bouton si la permission est accordée
            if (btn) btn.classList.add('hidden');
            
            // Toujours synchroniser le jeton navigateur avec Laravel
            syncSubscriptionWithServer(true);

        } else if (Notification.permission === 'default') {
            // Afficher le bouton pour demander l'autorisation
            if (btn) btn.classList.remove('hidden');

        } else if (Notification.permission === 'denied') {
            if (btn) btn.classList.add('hidden');
        }
    }

    // 3. Action déclenchée par le bouton utilisateur
    function enablePushNotifications() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            alert('Les notifications Push ne sont pas supportées par ce navigateur.');
            return;
        }

        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                syncSubscriptionWithServer(true);
            }
        });
    }

    // 4. Récupère/Crée l'abonnement navigateur et l'envoie à Laravel
    function syncSubscriptionWithServer(showAlert = false) {
        navigator.serviceWorker.ready.then(registration => {
            registration.pushManager.getSubscription().then(subscription => {
                if (subscription) {
                    // L'abonnement existe déjà dans le navigateur -> Envoi à Laravel
                    return sendSubscriptionToBackend(subscription, showAlert);
                }

                // Sinon, création d'un nouvel abonnement VAPID
                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                }).then(newSubscription => {
                    sendSubscriptionToBackend(newSubscription, showAlert);
                });
            }).catch(err => console.error('Erreur souscription Push:', err));
        });
    }

    // 5. Envoi AJAX sécurisé vers Laravel
    function sendSubscriptionToBackend(subscription, showAlert) {
        return fetch("{{ route('push.subscribe') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(subscription)
        })
        .then(res => {
            if (!res.ok) throw new Error('Erreur lors de la sauvegarde du Push');
            return res.json();
        })
        .then(data => {
            console.log('Abonnement Push synchronisé avec Laravel.');
            const btn = document.getElementById('btn-push-notif');
            if (btn) btn.classList.add('hidden');

            // if (showAlert) {
            //     alert('Notifications Push activées sur cet appareil !');
            // }
        })
        .catch(err => console.error('Erreur enregistrement serveur:', err));
    }

    // 6. Gestionnaire pour le composant Alpine.js (Alerte Plein Écran)
    function fullScreenAlertManager() {
        return {
            showAlert: false,
            alertData: { title: '', body: '', url: '' },
            init() {
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
        };
    }

    // 7. Utilitaire de conversion VAPID
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }
</script>
    @stack('scripts')
</body>
</html>