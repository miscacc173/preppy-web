# Preppy FCM setup

Preppy now supports end-to-end Firebase Cloud Messaging for web and device-token registration for iOS/Android.

## Firebase Console

1. Create/select the Firebase project.
2. Add a Web App and copy its web configuration values.
3. In **Project settings → Cloud Messaging → Web Push certificates**, create/copy the public VAPID key.
4. Enable the **Firebase Cloud Messaging API (HTTP v1)** in the linked Google Cloud project.
5. Create a service account with permission to send FCM messages and download its JSON credentials.

## Environment

```env
FCM_ENABLED=true
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase/service-account.json

FIREBASE_WEB_API_KEY=
FIREBASE_WEB_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_WEB_PROJECT_ID=your-project-id
FIREBASE_WEB_STORAGE_BUCKET=your-project.firebasestorage.app
FIREBASE_WEB_MESSAGING_SENDER_ID=
FIREBASE_WEB_APP_ID=
FIREBASE_WEB_VAPID_KEY=
```

Instead of `FIREBASE_SERVICE_ACCOUNT_PATH`, the server also accepts `FIREBASE_SERVICE_ACCOUNT_JSON`, or `FIREBASE_CLIENT_EMAIL` plus `FIREBASE_PRIVATE_KEY`.

After editing production environment values, run:

```bash
php artisan config:clear
php artisan config:cache
```

## Web verification

1. Sign in to Preppy.
2. Open **Settings → Notifications**.
3. Select **Enable push** and allow browser notifications.
4. Select **Send test**.
5. The browser should receive `Preppy notifications are working`.

The browser token is stored against the authenticated Preppy user in `user_devices`. On logout the current browser registration is deactivated. If the same browser later signs in as another user, registering the same FCM token deactivates any previous user's ownership of that token.

## Native apps

The existing authenticated endpoint can also register native FCM tokens:

`POST /api/v1/push/devices`

```json
{
  "platform": "ios",
  "device_id": "stable-device-id",
  "push_token": "FCM_TOKEN",
  "timezone": "Asia/Karachi",
  "metadata": { "app_version": "1.0.0" }
}
```

Use `platform: android` for Android. The backend delivery service is shared across web/iOS/Android.
