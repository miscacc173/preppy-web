import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const source = (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("web FCM initializes Firebase Messaging, requests permission, obtains a token and registers the device", async () => {
  const [push, main, settings, worker] = await Promise.all([
    source("resources/js/lib/pushNotifications.ts"),
    source("resources/js/main.ts"),
    source("resources/js/views/Settings.vue"),
    source("public/firebase-messaging-sw.js"),
  ]);
  assert.match(push, /Notification\.requestPermission/);
  assert.match(push, /messaging\.getToken/);
  assert.match(push, /\/push\/devices/);
  assert.match(main, /syncGrantedPushRegistration/);
  assert.match(settings, /Enable push/);
  assert.match(settings, /Send test/);
  assert.match(worker, /onBackgroundMessage/);
  assert.match(worker, /notificationclick/);
});

test("backend FCM uses HTTP v1 service-account auth and current-user device registration", async () => {
  const [client, service, controller, routes, config] = await Promise.all([
    source("app/Services/Notifications/FcmClient.php"),
    source("app/Services/Notifications/PushNotificationService.php"),
    source("app/Http/Controllers/Api/PushNotificationController.php"),
    source("routes/api.php"),
    source("config/firebase.php"),
  ]);
  assert.match(client, /firebase\.messaging/);
  assert.match(config, /oauth2\.googleapis\.com/);
  assert.match(client, /openssl_sign/);
  assert.match(service, /NotificationPolicy/);
  assert.match(controller, /UserDevice::updateOrCreate/);
  assert.match(controller, /where\('user_id', '!=', \$request->user\(\)->id\)/);
  assert.match(routes, /push\/config/);
  assert.match(routes, /push\/devices/);
  assert.match(routes, /push\/test/);
  assert.match(config, /FIREBASE_WEB_VAPID_KEY|vapid_key/);
});
