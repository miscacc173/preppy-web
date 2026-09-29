import { captureGooglePay, captureMockPayment, changeSubscriptionPlan, changeSubscriptionState, previewSubscriptionPlanChange, checkoutSubscription, convertGuestDemo, createSubscription, getAccount, getCatalog, getReferralSummary, getSubscriptions, requestGuestDemoEmailVerification, saveGuestDemoProgress, saveNotificationPreferences, startGuestDemo, updateAccount, validatePromotion, verifyGuestDemoEmail } from "@/lib/platformApi";
import { mapAccountBundle, mapCatalog, mapSubscription } from "@/data/mappers/platformMapper";
import type { UiNotificationPreference } from "@/data/models/platform";
import { api } from "@/lib/api";

export const platformRepository = {
  async catalog() { return mapCatalog(await getCatalog()); },
  async account() { return mapAccountBundle(await getAccount()); },
  async tutorAcademicAccess() { return (await api<{ data: { subject_ids: number[] } }>("/tutor/academic-access")).data; },
  async updateAccount(payload: Record<string, unknown>) { return mapAccountBundle(await updateAccount(payload)); },
  async subscriptions() { return (await getSubscriptions()).data.map(mapSubscription); },
  async subscribe(profileId: number, planId: number) { return mapSubscription((await createSubscription(profileId, planId)).data); },
  async checkout(profileId: number, planId: number, provider: "jazzcash" | "google_pay" | "mock", promoCode?: string, referralCode?: string) {
    const response = (await checkoutSubscription(profileId, planId, provider, promoCode, referralCode)).data;
    return { ...response, subscription: response.subscription ? mapSubscription(response.subscription) : null };
  },
  async validatePromotion(profileId: number, planId: number, code: string) { return (await validatePromotion(profileId, planId, code)).data; },
  async referralSummary() { return (await getReferralSummary()).data; },
  captureGooglePay,
  captureMockPayment,
  async changeSubscription(id: number, action: "cancel" | "resume" | "pause" | "activate") {
    return mapSubscription((await changeSubscriptionState(id, action)).data);
  },
  async previewPlanChange(id: number, planId: number, promoCode?: string) {
    return (await previewSubscriptionPlanChange(id, planId, promoCode)).data;
  },
  async changePlan(id: number, planId: number) {
    const response = await changeSubscriptionPlan(id, planId);
    return { ...response, subscription: mapSubscription(response.data) };
  },
  async checkoutPlanChange(id: number, planId: number, provider: "jazzcash" | "google_pay" | "mock", promoCode?: string, referralCode?: string) {
    const response = await changeSubscriptionPlan(id, planId, provider, promoCode, referralCode);
    return { ...response, subscription: mapSubscription(response.data) };
  },
  async requestGuestDemoEmailVerification(email: string) {
    return (await requestGuestDemoEmailVerification(email)).data;
  },
  async verifyGuestDemoEmail(challengeId: string, code: string) {
    return (await verifyGuestDemoEmail(challengeId, code)).data;
  },
  async startGuestDemo(payload: { verification_grant: string; course_id: number; exam_board_id: number; subject_ids: number[]; metadata?: Record<string, unknown> }) {
    return startGuestDemo(payload);
  },
  async saveGuestDemoProgress(id: string, accessToken: string, progress: Record<string, unknown>) {
    return saveGuestDemoProgress(id, accessToken, progress);
  },
  async convertGuestDemo(id: string, accessToken: string, payload: { name: string; password: string; password_confirmation: string }) {
    return convertGuestDemo(id, accessToken, payload);
  },
  async saveNotifications(preferences: UiNotificationPreference[]) {
    const response = await saveNotificationPreferences(preferences.map((item) => ({
      notification_type: item.type,
      channel: item.channel,
      is_enabled: item.enabled,
      schedule: item.schedule,
    })));
    return response.data.map((item) => ({ type: item.notification_type, channel: item.channel, enabled: item.is_enabled, schedule: item.schedule ?? {} }));
  },
  submitContact(payload: { name: string; email: string; subject: string; category?: string; message: string; kind: "contact" | "support" }) {
    return api<{ data: { id: number } }>("/contact-messages", { method: "POST", body: JSON.stringify(payload) });
  },
};
