import { api, storeCurrentUser, type AuthUser } from "@/lib/api";
import { createBillingKey } from "@/data/billing.js";

export type ExamBoard = { id: number; name: string; code: string; is_active: boolean };
export type Subject = { id: number; name: string; code: string; status: string; metadata?: Record<string, unknown> };
export type Course = {
  id: number;
  exam_board_id: number | null;
  name: string;
  code: string;
  level: string;
  subjects?: Subject[];
};
export type Plan = {
  id: number;
  name: string;
  code: string;
  scope: string;
  price_minor: number;
  currency: string;
  billing_interval: string;
  billing_interval_count: number;
  trial_days: number;
  features?: Record<string, boolean> | string[];
  course?: Course | null;
  subjects?: Subject[];
};
export type StudentProfile = {
  id: number;
  user_id: number;
  course_id: number | null;
  exam_board_id: number | null;
  school_year: string | null;
  school_name: string | null;
  preferences?: Record<string, unknown> | null;
  metadata?: Record<string, unknown> | null;
  course?: Course | null;
  exam_board?: ExamBoard | null;
  subjects?: Subject[];
};
export type NotificationPreference = {
  id: number;
  notification_type: string;
  channel: "email" | "push" | "sms" | "in_app";
  is_enabled: boolean;
  schedule?: Record<string, unknown>;
};
export type AccountUser = AuthUser & { student_profile?: StudentProfile | null };
export type Subscription = {
  id: number;
  status: string;
  plan_id: number;
  trial_ends_at: string | null;
  current_period_ends_at: string | null;
  cancel_at_period_end: boolean;
  plan?: Plan;
};
export type Paginated<T> = { data: T[]; current_page: number; last_page: number; total: number };

export async function getCatalog() {
  return (await api<{ data: { exam_boards: ExamBoard[]; courses: Course[]; subjects: Subject[]; plans: Plan[] } }>("/catalog")).data;
}

export async function getAccount() {
  const response = await api<{ data: AccountUser; notification_preferences: NotificationPreference[] }>("/account");
  storeCurrentUser(response.data);
  return response;
}

export async function updateAccount(payload: Record<string, unknown>) {
  const response = await api<{ data: AccountUser; notification_preferences: NotificationPreference[] }>("/account", {
    method: "PATCH",
    body: JSON.stringify(payload),
  });
  storeCurrentUser(response.data);
  return response;
}

export async function saveNotificationPreferences(preferences: Array<Omit<NotificationPreference, "id">>) {
  return api<{ data: NotificationPreference[] }>("/account/notification-preferences", {
    method: "PUT",
    body: JSON.stringify({ preferences }),
  });
}

export async function getSubscriptions() {
  return api<Paginated<Subscription>>("/subscriptions");
}

export async function createSubscription(studentProfileId: number, planId: number) {
  return api<{ data: Subscription }>("/subscriptions", {
    method: "POST",
    body: JSON.stringify({ student_profile_id: studentProfileId, plan_id: planId }),
  });
}

export type CheckoutResponse = {
  data: {
    subscription?: Subscription;
    invoice?: Record<string, unknown>;
    payment?: { id: number; status: string };
    gateway?: Record<string, unknown>;
    requires_payment: boolean;
  };
};

export async function checkoutSubscription(studentProfileId: number, planId: number, provider: "jazzcash" | "google_pay" | "mock", promoCode?: string, referralCode?: string) {
  return api<CheckoutResponse>("/checkout/subscriptions", { method: "POST", headers: { "Idempotency-Key": createBillingKey("subscription") }, body: JSON.stringify({ student_profile_id: studentProfileId, plan_id: planId, provider, promo_code: promoCode || undefined, referral_code: referralCode || undefined }) });
}

export async function validatePromotion(studentProfileId: number, planId: number, code: string) {
  return api<{ data: { code: string; name: string; discount_minor: number; subtotal_minor: number; total_minor: number; discount_type: string } }>("/promotions/validate", { method: "POST", body: JSON.stringify({ student_profile_id: studentProfileId, plan_id: planId, code }) });
}

export async function getReferralSummary() {
  return api<{ data: { referral_code: string; referrals: Array<Record<string, unknown>>; rewards: Array<Record<string, unknown>> } }>("/referrals/me");
}

export async function captureGooglePay(paymentId: number, paymentToken: string) {
  return api<{ data: { id: number; status: string } }>(`/payments/${paymentId}/capture/google-pay`, { method: "POST", body: JSON.stringify({ payment_token: paymentToken }) });
}

export async function captureMockPayment(paymentId: number, paymentToken: "mock_success" | "mock_decline" = "mock_success") {
  return api<{ data: { id: number; status: string; receipt_number?: string | null } }>(`/payments/${paymentId}/capture/mock`, { method: "POST", body: JSON.stringify({ payment_token: paymentToken }) });
}

export type PlanChangeQuote = {
  type: "upgrade" | "downgrade";
  current_plan_id: number;
  target_plan_id: number;
  current_price_minor: number;
  target_price_minor: number;
  unused_credit_minor: number;
  subtotal_after_credit_minor: number;
  promotion_discount_minor?: number;
  total_minor?: number;
  currency: string;
  effective_at: string;
};

export async function previewSubscriptionPlanChange(id: number, planId: number, promoCode?: string) {
  return api<{ data: PlanChangeQuote }>(`/subscriptions/${id}/change-plan/preview`, { method: "POST", body: JSON.stringify({ plan_id: planId, promo_code: promoCode || undefined }) });
}

export async function changeSubscriptionPlan(id: number, planId: number, provider?: "jazzcash" | "google_pay" | "mock", promoCode?: string, referralCode?: string) {
  return api<{ data: Subscription; invoice?: Record<string, unknown>; payment?: { id: number; status: string }; gateway?: Record<string, unknown>; change: PlanChangeQuote; requires_payment: boolean }>(`/subscriptions/${id}/change-plan`, { method: "POST", headers: provider ? { "Idempotency-Key": createBillingKey("plan-change") } : undefined, body: JSON.stringify({ plan_id: planId, provider, promo_code: promoCode || undefined, referral_code: referralCode || undefined }) });
}

export async function changeSubscriptionState(id: number, action: "cancel" | "resume" | "pause" | "activate") {
  return api<{ data: Subscription }>(`/subscriptions/${id}/${action}`, { method: "POST" });
}

export type GuestDemoEmailVerificationChallenge = {
  id: string;
  email: string;
  expires_at: string;
  resend_available_at: string;
  development_bypass: boolean;
  verification_grant?: string;
  verification_grant_expires_at?: string;
};

export type GuestDemoVerificationGrant = {
  email: string;
  verification_grant: string;
  verification_grant_expires_at: string;
};

export type GuestDemoSessionResponse = {
  data: { id: string; email: string; email_verified_at: string; metadata?: Record<string, unknown> };
  access_token: string;
};

export async function requestGuestDemoEmailVerification(email: string) {
  return api<{ data: GuestDemoEmailVerificationChallenge }>("/guest-demos/email-verification", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

export async function verifyGuestDemoEmail(challengeId: string, code: string) {
  return api<{ data: GuestDemoVerificationGrant }>(`/guest-demos/email-verification/${challengeId}/verify`, {
    method: "POST",
    body: JSON.stringify({ code }),
  });
}

export async function startGuestDemo(payload: { verification_grant: string; course_id: number; exam_board_id: number; subject_ids: number[]; metadata?: Record<string, unknown> }) {
  return api<GuestDemoSessionResponse>("/guest-demos", { method: "POST", body: JSON.stringify(payload) });
}

export async function saveGuestDemoProgress(id: string, accessToken: string, progressSnapshot: Record<string, unknown>) {
  return api(`/guest-demos/${id}/progress`, {
    method: "PUT",
    headers: { "X-Guest-Demo-Token": accessToken },
    body: JSON.stringify({ progress_snapshot: progressSnapshot }),
  });
}

export async function convertGuestDemo(id: string, accessToken: string, payload: { name: string; password: string; password_confirmation: string; school_year?: string; school_name?: string }) {
  return api<{ data: AuthUser; token: string }>(`/guest-demos/${id}/convert`, {
    method: "POST",
    headers: { "X-Guest-Demo-Token": accessToken },
    body: JSON.stringify(payload),
  });
}
