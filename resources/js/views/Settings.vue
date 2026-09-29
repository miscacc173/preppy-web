<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { User, Bell, CreditCard, Palette, Lock, ChevronRight, Check, Gift, Copy } from "lucide-vue-next";
import { useTheme } from "@/lib/theme";
import Switch from "./settings/Switch.vue";
import { useAuthSession } from "@/lib/authSession";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiNotificationPreference, UiSubscription } from "@/data/models/platform";
import { billingIntervalSuffix, formatMoney } from "@/lib/money";
import { enablePushNotifications, getPushStatus, sendTestPush, type PushStatus } from "@/lib/pushNotifications";

setPageMeta({ title: "Settings — Preppy" });

const TABS = [
  { id: "profile", label: "Profile", icon: User },
  { id: "notifications", label: "Notifications", icon: Bell },
  { id: "subscription", label: "Subscription", icon: CreditCard },
  { id: "referrals", label: "Refer a friend", icon: Gift },
  { id: "theme", label: "Theme", icon: Palette },
  { id: "account", label: "Account", icon: Lock },
] as const;

const PROFILE_FIELDS = [
  { l: "Name", k: "name" },
  { l: "Email", k: "email" },
  { l: "School year", k: "year" },
  { l: "Exam board", k: "board", readonly: true },
] as const;

const NOTIF_ITEMS = [
  { k: "practice", l: "Practice reminders", d: "Daily nudge to keep your streak alive." },
  { k: "exam", l: "Exam countdown", d: "Weekly summary of exam-day readiness." },
  { k: "retry", l: "Retry reminders", d: "Tell me when answers need a second pass." },
  { k: "weekly", l: "Weekly digest", d: "Email a Sunday review of progress." },
  { k: "promotions", l: "Offers & promotions", d: "Receive optional Preppy offers, discounts and promotional announcements." },
] as const;

const { signingOut, signOut } = useAuthSession();
const tab = ref<(typeof TABS)[number]["id"]>("profile");
const { theme, setTheme } = useTheme();
const profile = reactive({ name: "", email: "", year: "", board: "" });
const notif = reactive({ practice: true, exam: true, weekly: false, retry: true, promotions: false });
const subscription = ref<UiSubscription | null>(null);
const loading = ref(true);
const saving = ref(false);
const message = ref("");
const error = ref("");
const referralSummary = ref<{ referral_code: string; referrals: Array<Record<string, unknown>>; rewards: Array<Record<string, unknown>> } | null>(null);
const referralCopied = ref(false);
const pushStatus = ref<PushStatus | null>(null);
const pushBusy = ref(false);

const subscriptionPrice = computed(() => {
  const plan = subscription.value?.plan;
  if (!plan) return "No active subscription";
  return `${formatMoney(plan.priceMinor, plan.currency)}/${billingIntervalSuffix(plan.billingInterval)}`;
});

function preferenceEnabled(items: UiNotificationPreference[], type: string) {
  return items.find((item) => item.type === type)?.enabled ?? false;
}

onMounted(async () => {
  try {
    const [bundle, subscriptions, referrals] = await Promise.all([platformRepository.account(), platformRepository.subscriptions(), platformRepository.referralSummary()]);
    profile.name = bundle.account.name;
    profile.email = bundle.account.email;
    profile.year = bundle.account.schoolYear;
    profile.board = bundle.account.examBoard?.name ?? "Not selected";
    notif.practice = preferenceEnabled(bundle.notifications, "study_reminder");
    notif.exam = preferenceEnabled(bundle.notifications, "exam_countdown");
    notif.retry = preferenceEnabled(bundle.notifications, "retry_reminder");
    notif.weekly = preferenceEnabled(bundle.notifications, "performance_summary");
    notif.promotions = preferenceEnabled(bundle.notifications, "marketing_promotions");
    subscription.value = subscriptions[0] ?? null;
    referralSummary.value = referrals;
    pushStatus.value = await getPushStatus();
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Settings could not be loaded.";
  } finally {
    loading.value = false;
  }
});

async function saveProfile() {
  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    await platformRepository.updateAccount({ name: profile.name, email: profile.email, school_year: profile.year || null });
    message.value = "Profile saved.";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Profile could not be saved.";
  } finally {
    saving.value = false;
  }
}

async function saveNotifications() {
  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    await platformRepository.saveNotifications([
      { type: "study_reminder", channel: "push", enabled: notif.practice, schedule: {} },
      { type: "exam_countdown", channel: "push", enabled: notif.exam, schedule: {} },
      { type: "retry_reminder", channel: "push", enabled: notif.retry, schedule: {} },
      { type: "performance_summary", channel: "email", enabled: notif.weekly, schedule: {} },
      { type: "marketing_promotions", channel: "push", enabled: notif.promotions, schedule: {} },
    ]);
    message.value = "Notification preferences saved.";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Notification preferences could not be saved.";
  } finally {
    saving.value = false;
  }
}

async function enableBrowserPush() {
  pushBusy.value = true;
  error.value = "";
  message.value = "";
  try {
    pushStatus.value = await enablePushNotifications();
    message.value = "Browser push notifications enabled.";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Push notifications could not be enabled.";
    pushStatus.value = await getPushStatus();
  } finally {
    pushBusy.value = false;
  }
}

async function testBrowserPush() {
  pushBusy.value = true;
  error.value = "";
  message.value = "";
  try {
    await sendTestPush();
    message.value = "Test notification sent. It may take a few seconds to appear.";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Test push could not be sent.";
  } finally {
    pushBusy.value = false;
  }
}

async function updateSubscription(action: "cancel" | "resume") {
  if (!subscription.value) return;
  saving.value = true;
  error.value = "";
  try {
    subscription.value = await platformRepository.changeSubscription(subscription.value.id, action);
    message.value = action === "cancel" ? "Subscription will cancel at the end of the period." : "Subscription resumed.";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Subscription could not be updated.";
  } finally {
    saving.value = false;
  }
}

async function copyReferralCode() {
  if (!referralSummary.value?.referral_code) return;
  await navigator.clipboard.writeText(referralSummary.value.referral_code);
  referralCopied.value = true; window.setTimeout(() => referralCopied.value = false, 1500);
}

</script>

<template>
  <AppShell title="Settings" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6">
      <nav class="flex lg:flex-col gap-1 overflow-x-auto -mx-4 px-4 lg:mx-0 lg:px-0">
        <button
          v-for="t in TABS"
          :key="t.id"
          :class="`shrink-0 inline-flex items-center gap-2.5 px-3 py-2.5 rounded-md text-[13px] font-medium transition ${
            t.id === tab ? 'bg-blue-light text-blue' : 'text-ink2 hover:bg-bg-soft'
          }`"
          @click="tab = t.id"
        >
          <component :is="t.icon" :size="15" /> {{ t.label }}
        </button>
      </nav>

      <div class="bg-bg-card border border-border rounded-2xl p-6 lg:p-7">
        <p v-if="loading" class="mb-5 text-[13px] text-ink3">Loading settings…</p>
        <p v-if="message" class="mb-5 rounded-lg bg-mint-light px-4 py-3 text-[13px] text-mint">{{ message }}</p>
        <p v-if="error" class="mb-5 rounded-lg bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
        <template v-if="tab === 'profile'">
          <h3 class="text-[18px] font-semibold text-ink mb-1">Profile</h3>
          <p class="text-[13px] text-ink3 font-light mb-6">How Preppy identifies you.</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-[600px]">
            <div v-for="f in PROFILE_FIELDS" :key="f.k">
              <label class="block text-[12px] font-medium text-ink2 mb-1.5">{{ f.l }}</label>
              <input v-model="profile[f.k]" :readonly="'readonly' in f" :class="`w-full bg-bg-soft border border-border rounded-md px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-blue ${'readonly' in f ? 'opacity-70 cursor-not-allowed' : ''}`" />
            </div>
          </div>
          <button :disabled="saving || loading" class="mt-6 bg-blue text-white text-[13px] font-semibold px-5 py-2.5 rounded-md hover:opacity-90 disabled:opacity-50" @click="saveProfile">{{ saving ? "Saving…" : "Save changes" }}</button>
        </template>

        <template v-else-if="tab === 'notifications'">
          <h3 class="text-[18px] font-semibold text-ink mb-1">Notifications</h3>
          <p class="text-[13px] text-ink3 font-light mb-6">Choose when Preppy nudges you.</p>
          <div class="mb-5 max-w-[560px] rounded-xl border border-border bg-bg-soft px-4 py-4">
            <div class="flex items-start justify-between gap-4 flex-wrap">
              <div>
                <p class="text-[13.5px] font-medium text-ink">Browser push delivery</p>
                <p class="text-[12px] text-ink3 mt-1" v-if="pushStatus?.supported && pushStatus?.configured && pushStatus?.permission === 'granted'">Enabled on this browser.</p>
                <p class="text-[12px] text-ink3 mt-1" v-else-if="pushStatus?.supported && pushStatus?.configured && pushStatus?.permission === 'denied'">Blocked by your browser. Re-enable notifications in the browser's site settings.</p>
                <p class="text-[12px] text-ink3 mt-1" v-else-if="pushStatus?.supported && pushStatus?.configured">Available, but browser permission has not been granted yet.</p>
                <p class="text-[12px] text-ink3 mt-1" v-else-if="pushStatus?.supported">FCM is not configured on this environment.</p>
                <p class="text-[12px] text-ink3 mt-1" v-else>Web push is not supported by this browser.</p>
              </div>
              <div class="flex gap-2">
                <button v-if="pushStatus?.supported && pushStatus?.configured && pushStatus?.permission !== 'granted'" :disabled="pushBusy" class="bg-blue text-white text-[12px] font-semibold px-4 py-2 rounded-md hover:opacity-90 disabled:opacity-50" @click="enableBrowserPush">{{ pushBusy ? "Enabling…" : "Enable push" }}</button>
                <button v-if="pushStatus?.supported && pushStatus?.configured && pushStatus?.permission === 'granted'" :disabled="pushBusy" class="bg-bg-card border border-border text-ink2 text-[12px] font-semibold px-4 py-2 rounded-md hover:bg-bg-soft2 disabled:opacity-50" @click="testBrowserPush">{{ pushBusy ? "Sending…" : "Send test" }}</button>
              </div>
            </div>
          </div>
          <div class="space-y-1 max-w-[560px]">
            <label v-for="item in NOTIF_ITEMS" :key="item.k" class="flex items-start justify-between gap-4 py-4 border-b border-border last:border-0 cursor-pointer">
              <div>
                <p class="text-[14px] font-medium text-ink">{{ item.l }}</p>
                <p class="text-[12.5px] text-ink3 font-light mt-0.5">{{ item.d }}</p>
              </div>
              <Switch v-model:on="notif[item.k]" />
            </label>
          </div>
          <button :disabled="saving || loading" class="mt-5 bg-blue text-white text-[13px] font-semibold px-5 py-2.5 rounded-md hover:opacity-90 disabled:opacity-50" @click="saveNotifications">{{ saving ? "Saving…" : "Save preferences" }}</button>
        </template>

        <template v-else-if="tab === 'subscription'">
          <h3 class="text-[18px] font-semibold text-ink mb-1">Subscription</h3>
          <p class="text-[13px] text-ink3 font-light mb-6">Manage your Preppy plan.</p>
          <div class="bg-bg-soft border border-border rounded-xl p-5 max-w-[520px] flex items-center justify-between flex-wrap gap-4">
            <div>
              <p class="text-[11px] font-semibold text-blue uppercase tracking-wider mb-1">{{ subscription?.plan?.name ?? "No plan" }} · {{ subscription?.status ?? "inactive" }}</p>
              <p class="text-[16px] font-semibold text-ink">{{ subscriptionPrice }}</p>
              <p class="text-[12.5px] text-ink3 mt-0.5">Period ends {{ subscription?.periodEnd ? new Date(subscription.periodEnd).toLocaleDateString() : "—" }}.</p>
            </div>
            <RouterLink :to="{ path: '/pricing', query: { from: 'settings' } }" class="text-[13px] text-blue font-medium inline-flex items-center gap-1">
              Change plan <ChevronRight :size="13" />
            </RouterLink>
          </div>
          <div class="mt-4 flex gap-3 flex-wrap">
            <button disabled class="text-[13px] bg-bg-card border border-border text-ink3 rounded-md px-4 py-2 cursor-not-allowed">Card provider not configured</button>
            <button v-if="subscription && !subscription.cancelAtPeriodEnd" :disabled="saving" class="text-[13px] bg-bg-card border border-border text-pink rounded-md px-4 py-2 hover:bg-pink-light" @click="updateSubscription('cancel')">Cancel subscription</button>
            <button v-else-if="subscription" :disabled="saving" class="text-[13px] bg-bg-card border border-border text-blue rounded-md px-4 py-2 hover:bg-blue-light" @click="updateSubscription('resume')">Resume subscription</button>
          </div>
        </template>

        <template v-else-if="tab === 'referrals'">
          <h3 class="text-[18px] font-semibold text-ink mb-1">Refer a friend</h3>
          <p class="text-[13px] text-ink3 font-light mb-6">Share your referral code. Your reward promo code is created only after your friend completes a successful payment.</p>
          <div class="max-w-[620px] space-y-4">
            <div class="rounded-xl border border-border bg-bg-soft p-5"><p class="text-[11px] font-semibold uppercase tracking-wider text-ink3">Your referral code</p><div class="mt-2 flex items-center gap-2"><strong class="font-mono text-[20px] tracking-wider text-ink">{{ referralSummary?.referral_code || '—' }}</strong><button class="ops-button-secondary inline-flex items-center gap-1.5" @click="copyReferralCode"><Copy :size="13" /> {{ referralCopied ? 'Copied' : 'Copy' }}</button></div></div>
            <div class="grid gap-3 sm:grid-cols-2"><div class="rounded-xl border border-border p-4"><p class="text-[11px] text-ink3">Friends referred</p><strong class="text-[24px]">{{ referralSummary?.referrals.length || 0 }}</strong></div><div class="rounded-xl border border-border p-4"><p class="text-[11px] text-ink3">Reward promo codes earned</p><strong class="text-[24px]">{{ referralSummary?.rewards.length || 0 }}</strong></div></div>
            <div v-if="referralSummary?.rewards.length" class="rounded-xl border border-border overflow-hidden"><div class="bg-bg-soft px-4 py-3 text-[12px] font-semibold">Your reward promo codes</div><div v-for="reward in referralSummary.rewards" :key="String(reward.id)" class="flex items-center justify-between border-t border-border px-4 py-3 text-[12px]"><span class="font-mono font-semibold">{{ reward.code }}</span><span class="text-ink3">{{ reward.discount_type === 'percentage' ? `${reward.percentage}% off` : 'Fixed discount' }}</span></div></div>
          </div>
        </template>

        <template v-else-if="tab === 'theme'">
          <h3 class="text-[18px] font-semibold text-ink mb-1">Theme</h3>
          <p class="text-[13px] text-ink3 font-light mb-6">Choose how Preppy looks. Your preference is saved on this device.</p>
          <div class="grid grid-cols-2 gap-3 max-w-[420px]">
            <button :class="`text-left rounded-xl p-3 transition ${theme === 'light' ? 'border-2 border-blue bg-bg-soft' : 'border border-border hover:bg-bg-soft'}`" @click="setTheme('light')">
              <div class="aspect-video bg-[#FAFBFF] rounded-md border border-[#E4E7F5] mb-2 grid place-items-center text-[10px] text-[#7880A4] font-mono">Aa · 71%</div>
              <p class="text-[12px] font-semibold text-ink flex items-center gap-1.5">Light <Check v-if="theme === 'light'" :size="12" class="text-blue" /></p>
            </button>
            <button :class="`text-left rounded-xl p-3 transition ${theme === 'dark' ? 'border-2 border-blue bg-bg-soft' : 'border border-border hover:bg-bg-soft'}`" @click="setTheme('dark')">
              <div class="aspect-video bg-[#0F172A] rounded-md border border-[#334155] mb-2 grid place-items-center text-[10px] text-[#CBD5E1] font-mono">Aa · 71%</div>
              <p class="text-[12px] font-semibold text-ink flex items-center gap-1.5">Dark <Check v-if="theme === 'dark'" :size="12" class="text-blue" /></p>
            </button>
          </div>
        </template>

        <template v-else-if="tab === 'account'">
          <h3 class="text-[18px] font-semibold text-ink mb-1">Account</h3>
          <p class="text-[13px] text-ink3 font-light mb-6">Password and account actions.</p>
          <div class="space-y-3 max-w-[420px]">
            <button disabled class="w-full text-left bg-bg-soft border border-border rounded-md px-4 py-3 opacity-60 cursor-not-allowed">
              <p class="text-[13.5px] font-medium text-ink">Change password</p>
              <p class="text-[12px] text-ink3">Use a strong password — at least 12 characters.</p>
            </button>
            <button disabled class="w-full text-left bg-bg-soft border border-border rounded-md px-4 py-3 opacity-60 cursor-not-allowed">
              <p class="text-[13.5px] font-medium text-ink">Download my data</p>
              <p class="text-[12px] text-ink3">All sessions, answers and reports as JSON.</p>
            </button>
            <button :disabled="signingOut" class="w-full text-left bg-bg-soft border border-border rounded-md px-4 py-3 hover:bg-pink-light text-pink disabled:opacity-50" @click="signOut()">
              <p class="text-[13.5px] font-medium">{{ signingOut ? "Signing out…" : "Sign out" }}</p>
            </button>
          </div>
        </template>
      </div>
    </div>
  </AppShell>
</template>