@php
    use App\Domain\Acces\Enums\PermissionEnum;
    use Illuminate\Support\Facades\Gate;
    use App\Domain\Acces\Enums\RoleEnum;
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titre-page', 'Gestion des appuis') | {{ config('app.name') }}</title>
    
    {{-- Balises PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    @include('layouts.partials.marque')
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    {{-- Livewire : seul l'assistant IA du tableau de bord en dépend. --}}
    @livewireStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[--color-page] text-[--color-ink] antialiased"
      x-data="{ menu: false }" @keydown.escape.window="menu = false">

<div class="min-h-screen lg:flex">

    {{-- Fond sombre derrière le menu, sur téléphone et tablette seulement. --}}
    <div x-show="menu" x-cloak x-transition.opacity @click="menu = false"
         class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    {{-- Menu : tiroir sur petit écran, colonne fixe à partir de lg. --}}
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col bg-[--color-primary] text-white shadow-xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:max-w-none lg:shrink-0 lg:translate-x-0 lg:shadow-none"
           aria-label="Navigation principale">

        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="min-w-0" aria-label="Tableau de bord ORYZA">
                {{-- Sur fond vert : version « négatif » (blanc + or), cf. charte. --}}
                <img src="/images/brand/ORYZA_logo_negatif.svg" alt="ORYZA, gestion des appuis de la filière riz" class="h-12 w-auto">
            </a>
            <button type="button" @click="menu = false" class="rounded-md p-1 text-white/80 hover:bg-white/10 lg:hidden" aria-label="Fermer le menu">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
            @include('layouts.partials.menu')
        </nav>

        <div class="border-t border-white/10 p-4">
            <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
            <p class="mb-3 truncate text-xs text-emerald-100/70">{{ auth()->user()->getRoleNames()->map(fn ($r) => \App\Domain\Acces\Enums\RoleEnum::tryFrom($r)?->libelle() ?? $r)->join(', ') }}</p>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-200 hover:bg-white/10">
                    <span class="material-symbols-outlined text-base">logout</span> Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">

        {{-- Barre supérieure : reste en vue quand on fait défiler. --}}
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-[--color-border] bg-white/95 px-4 backdrop-blur sm:px-6">
            <button type="button" @click="menu = true" class="-ml-1 rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Ouvrir le menu">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <span class="min-w-0 truncate text-sm font-semibold text-slate-700">@yield('titre-page', 'Bienvenue '.auth()->user()->name)</span>

            <div class="ml-auto flex items-center gap-2 sm:gap-4">

                {{-- Bouton d'activation des Push --}}
                <button id="btn-push-notif"
                        onclick="enablePushNotifications()"
                        class="hidden items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-emerald-800">
                    <span class="material-symbols-outlined text-sm">notifications_active</span>
                    <span class="hidden sm:inline">Activer les notifs</span>
                </button>

                {{-- Cloche : notifications non lues --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="relative rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-emerald-700 focus:outline-none" aria-label="Notifications">
                        <span class="material-symbols-outlined text-2xl">notifications</span>
                        @if (auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="fixed inset-x-3 top-16 z-50 rounded-xl border border-slate-200 bg-white p-4 shadow-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-80">
                        <div class="mb-2 flex items-center justify-between border-b pb-2">
                            <h3 class="text-sm font-bold text-slate-800">Notifications</h3>
                            <span class="text-xs text-slate-400">{{ config('app.name') }}</span>
                        </div>

                        <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                            @forelse (auth()->user()->unreadNotifications as $notification)
                                <div class="py-2 text-xs">
                                    <p class="font-semibold text-slate-800">{{ $notification->data['titre'] ?? 'Notification' }}</p>
                                    <p class="text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                        @if (! empty($notification->data['url']))
                                            <a href="{{ $notification->data['url'] }}" class="font-semibold text-emerald-700 hover:underline">Voir</a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="py-4 text-center text-xs text-slate-500">Aucune nouvelle notification</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @include('partials.flash')

            @yield('content')
        </main>
    </div>
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

    @livewireScripts

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