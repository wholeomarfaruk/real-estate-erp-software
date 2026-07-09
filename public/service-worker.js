self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    const payload = event.data.json();

    const options = {
        body: payload.body,
        icon: payload.icon,
        image: payload.image,
        badge: payload.badge,
        data: payload.data,
        actions: payload.actions,
        tag: payload.tag,
        requireInteraction: payload.requireInteraction,
        renotify: payload.renotify,
        vibrate: payload.vibrate,
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const actionUrl = event.notification.data?.action_url;
    if (!actionUrl) {
        return;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === actionUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(actionUrl);
            }
        })
    );
});
