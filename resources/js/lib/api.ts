const API_ROOT = "/api/v1";
const TOKEN_KEY = "aistar:auth-token";
const USER_KEY = "aistar:auth-user";
const LAST_USER_KEY = "aistar:last-user-id";

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  roles?: Array<{ slug: string; pivot?: { context_type?: string; context_id?: number | null } }>;
  student_profile?: Record<string, unknown> | null;
};

export function defaultAuthenticatedPath(user: AuthUser | null) {
  const roles = user?.roles ?? [];
  if (roles.some((role) => ["system_admin", "system_staff"].includes(role.slug))) return "/admin";
  const instituteRole = roles.find((role) => ["institute_admin", "institute_staff"].includes(role.slug) && role.pivot?.context_id);
  if (instituteRole?.pivot?.context_id) return `/institute/${instituteRole.pivot.context_id}`;
  if (roles.some((role) => role.slug === "teacher")) return "/teacher/workspace";
  if (roles.some((role) => role.slug === "tutor")) return "/learn";
  if (roles.some((role) => role.slug === "parent")) return "/parent";
  return "/dashboard";
}

type AuthResponse = { data: AuthUser; token: string };

export class ApiError extends Error {
  constructor(message: string, public status: number, public errors: Record<string, string[]> = {}) {
    super(message);
  }
}

export function token() { return localStorage.getItem(TOKEN_KEY); }
export function currentUser(): AuthUser | null {
  try { return JSON.parse(localStorage.getItem(USER_KEY) || "null"); } catch { return null; }
}
export function storeCurrentUser(user: AuthUser) {
  localStorage.setItem(USER_KEY, JSON.stringify(user));
  return user;
}
function clearLocalUserState() {
  const preserved = new Set([TOKEN_KEY, USER_KEY, LAST_USER_KEY, "aistar-theme"]);
  Object.keys(localStorage).filter((key) => key.startsWith("aistar") && !preserved.has(key)).forEach((key) => originalRemoveItem(key));
}

export function clearAuth(clearUserState = true) {
  if (clearUserState) clearLocalUserState();
  originalRemoveItem(TOKEN_KEY);
  originalRemoveItem(USER_KEY);
  window.dispatchEvent(new CustomEvent("aistar:auth-changed", { detail: { user: null } }));
}

function prepareIdentity(user: AuthUser) {
  const previousId = Number(localStorage.getItem(LAST_USER_KEY) || 0);
  if (previousId && previousId !== user.id) clearLocalUserState();
  originalSetItem(LAST_USER_KEY, String(user.id));
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const authToken = token();
  const response = await fetch(`${API_ROOT}${path}`, {
    ...init,
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      ...init.headers,
    },
  });
  const body = response.status === 204 ? null : await response.json().catch(() => null);
  if (!response.ok) {
    if (response.status === 401) clearAuth();
    throw new ApiError(body?.message || "The request could not be completed.", response.status, body?.errors || {});
  }
  return body as T;
}

export async function downloadApiFile(path: string, filename: string) {
  const authToken = token();
  const response = await fetch(`${API_ROOT}${path}`, { credentials: "same-origin", headers: { Accept: "application/pdf", ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}) } });
  if (!response.ok) throw new ApiError("The document could not be downloaded.", response.status);
  const url = URL.createObjectURL(await response.blob());
  const anchor = document.createElement("a"); anchor.href = url; anchor.download = filename; document.body.appendChild(anchor); anchor.click(); anchor.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export function storeAuth(response: AuthResponse) {
  prepareIdentity(response.data);
  originalSetItem(TOKEN_KEY, response.token);
  originalSetItem(USER_KEY, JSON.stringify(response.data));
  window.dispatchEvent(new CustomEvent("aistar:auth-changed", { detail: { user: response.data } }));
  return response.data;
}

export async function exchangeWebSession() {
  // Social OAuth (Google/Apple) authenticates a Laravel web session. Clear any prior browser
  // bearer identity first, then exchange the fresh web session for the same API
  // token shape used by normal login.
  clearAuth(true);
  return storeAuth(await api<AuthResponse>("/auth/exchange-web-session", { method: "POST" }));
}

export async function appleNativeLogin(identityToken: string, name?: string, deviceName = "apple-native", nonce?: string) {
  clearAuth(true);
  const response = await api<AuthResponse & { onboarding_required: boolean }>("/auth/apple", { method: "POST", body: JSON.stringify({ identity_token: identityToken, name, device_name: deviceName, nonce }) });
  const user = storeAuth(response);
  return { user, onboardingRequired: response.onboarding_required };
}

export async function login(email: string, password: string) {
  clearAuth(true);
  return storeAuth(await api<AuthResponse>("/auth/login", { method: "POST", body: JSON.stringify({ email, password, device_name: "web" }) }));
}

export type SignupEmailVerificationChallenge = {
  id: string;
  email: string;
  expires_at: string;
  resend_available_at: string;
  development_bypass: boolean;
  verification_grant?: string;
  verification_grant_expires_at?: string;
};

export type SignupEmailVerificationGrant = {
  email: string;
  verification_grant: string;
  verification_grant_expires_at: string;
};

export async function requestSignupEmailVerification(email: string) {
  return api<{ data: SignupEmailVerificationChallenge }>("/auth/register/email-verification", { method: "POST", body: JSON.stringify({ email }) });
}

export async function verifySignupEmail(challengeId: string, code: string) {
  return api<{ data: SignupEmailVerificationGrant }>(`/auth/register/email-verification/${challengeId}/verify`, { method: "POST", body: JSON.stringify({ code }) });
}

export async function register(name: string, email: string, password: string, verificationGrant: string, academic: { course_id: number; exam_board_id: number; subject_ids: number[] }) {
  return storeAuth(await api<AuthResponse>("/auth/register", { method: "POST", body: JSON.stringify({ name, email, password, password_confirmation: password, verification_grant: verificationGrant, ...academic }) }));
}

export async function fetchCurrentUser(): Promise<AuthUser | null> {
  try {
    const response = await api<{ data: AuthUser }>("/auth/me");
    return storeCurrentUser(response.data);
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) return null;
    throw error;
  }
}

let logoutRequest: Promise<void> | null = null;

export async function logout() {
  if (!logoutRequest) {
    logoutRequest = (async () => {
      try {
        // Stop this browser from receiving push notifications for the user being signed out.
        // A later login with notification permission already granted will register the same
        // browser/token against the new authenticated identity.
        const pushDeviceId = localStorage.getItem("preppy:fcm-device-id");
        if (pushDeviceId && token()) {
          await api(`/push/devices/${encodeURIComponent(pushDeviceId)}`, { method: "DELETE" }).catch(() => undefined);
        }
        // Always call logout: social sign-in may still have a server web
        // session even when no bearer token is currently stored.
        await api("/auth/logout", { method: "POST" });
      } catch {
        // Local logout still succeeds if the server session/token is already
        // gone or the request cannot be completed.
      } finally {
        // Logout remains locally successful if the token already expired or
        // the network is unavailable.
        clearAuth();
      }
    })().finally(() => { logoutRequest = null; });
  }
  return logoutRequest;
}

let hydrating = false;
let syncReady = false;
const timers = new Map<string, number>();
const originalSetItem = localStorage.setItem.bind(localStorage);
const originalRemoveItem = localStorage.removeItem.bind(localStorage);

function shouldSync(key: string) {
  return key.startsWith("aistar") && ![TOKEN_KEY, USER_KEY, LAST_USER_KEY, "aistar-theme"].includes(key);
}

function queueState(key: string, rawValue: string | null) {
  if (!syncReady || hydrating || !shouldSync(key)) return;
  const old = timers.get(key); if (old) window.clearTimeout(old);
  timers.set(key, window.setTimeout(() => {
    const encoded = encodeURIComponent(key);
    const request = rawValue === null
      ? api(`/app-state/${encoded}`, { method: "DELETE" })
      : api(`/app-state/${encoded}`, { method: "PUT", body: JSON.stringify({ value: safeParse(rawValue) }) });
    void request.catch(() => undefined);
  }, 250));
}

function safeParse(raw: string) { try { return JSON.parse(raw); } catch { return raw; } }

export async function hydrateAppState() {
  const response = await api<{ data: Record<string, unknown> }>("/app-state");
  hydrating = true;
  try {
    Object.entries(response.data || {}).forEach(([key, value]) => originalSetItem(key, JSON.stringify(value)));
  } finally { hydrating = false; }
  window.dispatchEvent(new Event("aistar:state-hydrated"));
}

export function setupStateSync() {
  if (syncReady) return;
  syncReady = true;
  localStorage.setItem = ((key: string, value: string) => { originalSetItem(key, value); queueState(key, value); }) as typeof localStorage.setItem;
  localStorage.removeItem = ((key: string) => { originalRemoveItem(key); queueState(key, null); }) as typeof localStorage.removeItem;
}
