/*
 * Service worker ORYZA : notifications push et mode hors-ligne.
 *
 * Mode hors-ligne, trois règles simples :
 *  - fichiers statiques (CSS, JS, images, polices) : servis depuis le cache, mis en cache au premier usage ;
 *  - pages consultées (GET) : le réseau d'abord ; sans réseau, la dernière copie gardée, sinon « hors-ligne.html » ;
 *  - envois de formulaires (POST, PUT, DELETE) : jamais mis en cache, ils partent au réseau ou échouent
 *    (le formulaire de demande garde alors sa saisie sur l'appareil, cf. resources/js/hors-ligne.js).
 *
 * Changer VERSION_CACHE vide les anciens caches au prochain chargement.
 */
const VERSION_CACHE = 'oryza-v2';
const CACHE_STATIQUE = `${VERSION_CACHE}-statique`;
const CACHE_PAGES = `${VERSION_CACHE}-pages`;
const PAGE_HORS_LIGNE = '/hors-ligne.html';

// Pages gardées pour la consultation hors ligne : celles du travail de terrain.
const PAGES_GARDEES = ['/dashboard', '/demandes', '/demandes/create', '/demandes/serie', '/attributions'];

// Événement d'installation : la page hors-ligne est mise de côté dès le départ.
self.addEventListener('install', function (event) {
    self.skipWaiting();
    event.waitUntil(caches.open(CACHE_STATIQUE).then((cache) => cache.addAll([PAGE_HORS_LIGNE, '/android-chrome-192x192.png'])));
});

self.addEventListener('fetch', function (event) {
    const requete = event.request;
    const url = new URL(requete.url);

    // Seules les lectures du site lui-même sont concernées.
    if (requete.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Fichiers statiques : le cache d'abord.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/') || /\.(png|svg|ico|woff2?)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(requete).then((enCache) => enCache || fetch(requete).then((reponse) => {
                if (reponse.ok) {
                    const copie = reponse.clone();
                    caches.open(CACHE_STATIQUE).then((cache) => cache.put(requete, copie));
                }
                return reponse;
            }))
        );
        return;
    }

    // Pages : le réseau d'abord, la copie gardée si le réseau manque.
    if (requete.mode === 'navigate') {
        event.respondWith(
            fetch(requete).then((reponse) => {
                if (reponse.ok && PAGES_GARDEES.includes(url.pathname)) {
                    const copie = reponse.clone();
                    caches.open(CACHE_PAGES).then((cache) => cache.put(requete, copie));
                }
                return reponse;
            }).catch(() => caches.match(requete).then((enCache) => enCache || caches.match(PAGE_HORS_LIGNE)))
        );
    }
});

// Canal de communication avec la page web
const alertChannel = new BroadcastChannel('varyza_alerts');

// Événement d'activation : on supprime les caches d'une version précédente.
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then((noms) => Promise.all(noms.filter((nom) => nom.startsWith('oryza-') && !nom.startsWith(VERSION_CACHE)).map((nom) => caches.delete(nom))))
            .then(() => clients.claim())
    );
});

// Événement Push (Réception de la notification)
self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (event.data) {
        try {
            const payload = event.data.json();

            if (payload.data && payload.data.fullscreen) {
                alertChannel.postMessage({
                    type: 'FULLSCREEN_ALERT',
                    payload: payload
                });
            }
            
            event.waitUntil(
                self.registration.showNotification(payload.title || 'Varyza', {
                    body: payload.body || '',
                    icon: payload.icon || '/android-chrome-192x192.png',
                    badge: payload.badge || '/images/icons/badge-96x96.png',
                    data: payload.data || {}
                })
            );
        } catch (e) {
            console.error('Erreur lecture payload notification:', e);
        }
    }
});

// Événement Clic sur la Notification
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});