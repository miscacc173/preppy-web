/* Preppy Firebase Cloud Messaging service worker. */
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

let messagingReady = false;

fetch('/api/v1/push/config', { headers: { Accept: 'application/json' } })
  .then((response) => response.ok ? response.json() : Promise.reject(new Error('FCM config unavailable')))
  .then(({ data }) => {
    if (!data?.enabled || !data.firebase_config) return;
    if (!firebase.apps.length) firebase.initializeApp(data.firebase_config);
    const messaging = firebase.messaging();
    messaging.onBackgroundMessage((payload) => {
      const data = payload?.data || {};
      const title = data.title || 'Preppy';
      return self.registration.showNotification(title, {
        body: data.body || 'You have a new notification.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: { url: data.url || '/notifications' },
        tag: data.type || undefined,
      });
    });
    messagingReady = true;
  })
  .catch(() => { messagingReady = false; });

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = event.notification?.data?.url || '/notifications';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      const sameOrigin = windowClients.find((client) => client.url.startsWith(self.location.origin));
      if (sameOrigin) {
        sameOrigin.focus();
        return sameOrigin.navigate(target);
      }
      return clients.openWindow(target);
    })
  );
});
