// Événement d'installation
self.addEventListener('install', function (event) {
    self.skipWaiting();
});

// Canal de communication avec la page web
const alertChannel = new BroadcastChannel('varyza_alerts');

// Événement d'activation
self.addEventListener('activate', function (event) {
    event.waitUntil(clients.claim());
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
                    icon: payload.icon || '/images/icons/icon-192x192.png',
                    badge: payload.badge || '/images/icons/icon-192x192.png',
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