const API_ROOT = "/api/v1";
const TOKEN_KEY = "aistar:auth-token";
const USER_KEY = "aistar:auth-user";

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
export function clearAuth() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  window.dispatchEvent(new CustomEvent("aistar:auth-changed", { detail: { user: null } }));
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
  localStorage.setItem(TOKEN_KEY, response.token);
  localStorage.setItem(USER_KEY, JSON.stringify(response.data));
  return response.data;
}

export async function login(email: string, password: string) {
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
        if (token()) await api("/auth/logout", { method: "POST" });
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
  return key.startsWith("aistar") && ![TOKEN_KEY, USER_KEY, "aistar-theme"].includes(key);
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
