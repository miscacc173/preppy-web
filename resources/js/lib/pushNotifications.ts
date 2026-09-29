import { api, currentUser } from "@/lib/api";

const DEVICE_ID_KEY = "preppy:fcm-device-id";
const FIREBASE_VERSION = "10.14.1";

type FirebaseCompat = {
  apps: unknown[];
  initializeApp: (config: Record<string, unknown>) => unknown;
  messaging: () => {
    getToken: (options: { vapidKey: string; serviceWorkerRegistration: ServiceWorkerRegistration }) => Promise<string>;
    onMessage: (callback: (payload: Record<string, any>) => void) => () => void;
  };
};

declare global {
  interface Window { firebase?: FirebaseCompat }
}

export type PushConfig = {
  enabled: boolean;
  firebase_config: Record<string, string | null>;
  vapid_key: string | null;
};

export type PushStatus = {
  supported: boolean;
  configured: boolean;
  permission: NotificationPermission | "unsupported";
  registered: boolean;
};

let configPromise: Promise<PushConfig> | null = null;
let foregroundBound = false;

function supported() {
  return typeof window !== "undefined" && "Notification" in window && "serviceWorker" in navigator;
}

async function loadScript(src: string, id: string) {
  if (document.getElementById(id)) return;
  await new Promise<void>((resolve, reject) => {
    const script = document.createElement("script");
    script.id = id;
    script.src = src;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Could not load ${src}`));
    document.head.appendChild(script);
  });
}

async function firebaseConfig(): Promise<PushConfig> {
  if (!configPromise) {
    configPromise = fetch("/api/v1/push/config", { headers: { Accept: "application/json" }, credentials: "same-origin" })
      .then(async (response) => {
        if (!response.ok) throw new Error("Push configuration could not be loaded.");
        return (await response.json()).data as PushConfig;
      });
  }
  return configPromise;
}

async function firebaseMessaging() {
  const config = await firebaseConfig();
  if (!config.enabled || !config.vapid_key) throw new Error("Push notifications are not configured on this environment.");

  await loadScript(`https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}/firebase-app-compat.js`, "firebase-app-compat");
  await loadScript(`https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}/firebase-messaging-compat.js`, "firebase-messaging-compat");
  if (!window.firebase) throw new Error("Firebase Messaging could not be initialized.");
  if (!window.firebase.apps.length) window.firebase.initializeApp(config.firebase_config);

  const registration = await navigator.serviceWorker.register("/firebase-messaging-sw.js", { scope: "/" });
  await navigator.serviceWorker.ready;
  const messaging = window.firebase.messaging();
  bindForegroundMessages(messaging);
  return { messaging, registration, config };
}

function bindForegroundMessages(messaging: ReturnType<FirebaseCompat["messaging"]>) {
  if (foregroundBound) return;
  foregroundBound = true;
  messaging.onMessage((payload) => {
    window.dispatchEvent(new CustomEvent("preppy:push-message", { detail: payload }));
    const data = payload.data ?? {};
    const title = data.title || "Preppy";
    const body = data.body || "You have a new notification.";
    if (Notification.permission === "granted") {
      void navigator.serviceWorker.ready.then((registration) => registration.showNotification(title, {
        body,
        icon: "/favicon.ico",
        badge: "/favicon.ico",
        data: { url: data.url || "/notifications" },
        tag: data.type || undefined,
      }));
    }
  });
}

export function pushDeviceId() {
  let id = localStorage.getItem(DEVICE_ID_KEY);
  if (!id) {
    id = typeof crypto !== "undefined" && "randomUUID" in crypto
      ? crypto.randomUUID()
      : `web-${Date.now()}-${Math.random().toString(36).slice(2)}`;
    localStorage.setItem(DEVICE_ID_KEY, id);
  }
  return id;
}

async function registerToken(token: string) {
  const user = currentUser();
  if (!user) throw new Error("Sign in before enabling push notifications.");
  await api("/push/devices", {
    method: "POST",
    body: JSON.stringify({
      platform: "web",
      device_id: pushDeviceId(),
      push_token: token,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
      metadata: { user_agent: navigator.userAgent, source: "web" },
    }),
  });
}

async function tokenAndRegister() {
  const { messaging, registration, config } = await firebaseMessaging();
  const token = await messaging.getToken({ vapidKey: config.vapid_key!, serviceWorkerRegistration: registration });
  if (!token) throw new Error("Firebase did not return a push token. Check the Web Push certificate/VAPID configuration.");
  await registerToken(token);
  return token;
}

export async function enablePushNotifications() {
  if (!supported()) throw new Error("This browser does not support web push notifications.");
  const permission = await Notification.requestPermission();
  if (permission !== "granted") throw new Error(permission === "denied" ? "Browser notification permission was denied." : "Browser notification permission was not granted.");
  await tokenAndRegister();
  return getPushStatus();
}

export async function syncGrantedPushRegistration() {
  if (!supported() || Notification.permission !== "granted" || !currentUser()) return false;
  try {
    const config = await firebaseConfig();
    if (!config.enabled) return false;
    await tokenAndRegister();
    return true;
  } catch {
    return false;
  }
}

export async function unregisterCurrentPushDevice() {
  if (!currentUser()) return;
  const id = localStorage.getItem(DEVICE_ID_KEY);
  if (!id) return;
  await api(`/push/devices/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export async function sendTestPush() {
  return api<{ data: { sent: number; failed: number; skipped: number } }>("/push/test", { method: "POST" });
}

export async function getPushStatus(): Promise<PushStatus> {
  if (!supported()) return { supported: false, configured: false, permission: "unsupported", registered: false };
  let configured = false;
  try { configured = (await firebaseConfig()).enabled; } catch { configured = false; }
  return {
    supported: true,
    configured,
    permission: Notification.permission,
    registered: configured && Notification.permission === "granted",
  };
}
