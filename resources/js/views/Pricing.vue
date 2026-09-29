<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import { Check, Lock, ArrowRight, ArrowLeft, Crown } from "lucide-vue-next";
import { ApiError, currentUser, token } from "@/lib/api";
import { checkDashboardAccess } from "@/lib/subscriptionAccess";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiAccount, UiPlan, UiSubscription } from "@/data/models/platform";
import { planMatchesAccount } from "@/data/planEligibility";
import { billingIntervalLabel, billingIntervalSuffix, formatMoney } from "@/lib/money";

setPageMeta({ title: "Pricing — Preppy", description: "Choose a plan and activate it securely." });

const catalogPlans = ref<UiPlan[]>([]);
const account = ref<UiAccount | null>(null);
const activeSubscription = ref<UiSubscription | null>(null);
const loading = ref(true);
const submitting = ref(false);
const error = ref("");
const provider = ref<"jazzcash" | "google_pay" | "mock">("jazzcash");
const isLoggedIn = ref(Boolean(token() || currentUser()));
const developmentPaymentBypass = ref(false);
const mockPaymentsEnabled = ref(false);
const mockCardNumber = ref("4242 4242 4242 4242");
const mockExpiry = ref("12/30");
const mockCvv = ref("123");
const promoCode = ref("");
const referralCode = ref("");
const promoPreview = ref<{ code: string; name: string; discount_minor: number; subtotal_minor: number; total_minor: number } | null>(null);
const promoMessage = ref("");
const validatingPromo = ref(false);
const planChangeQuote = ref<{ type: "upgrade" | "downgrade"; current_price_minor: number; target_price_minor: number; unused_credit_minor: number; subtotal_after_credit_minor: number; promotion_discount_minor?: number; total_minor?: number; currency: string; effective_at: string } | null>(null);
const planChangeMessage = ref("");

const route = useRoute();
const router = useRouter();
const plans = computed(() => account.value
  ? catalogPlans.value.filter((item) => planMatchesAccount(item, account.value!))
  : catalogPlans.value);
const fromGuestDiagnostic = computed(() => route.query.from === "guest-diagnostic");

const backLink = computed(() => {
  const from = route.query.from;
  if (from === "settings" || from === "billing") return { to: "/settings", label: "Back to Settings" } as const;
  if (from === "guest-diagnostic") return { to: "/demo", label: "Back to diagnostic" } as const;
  if (from === "dashboard") return { to: "/dashboard", label: "Back to Dashboard" } as const;
  if (from === "landing" || from === "home") return { to: "/", label: "Back to Home" } as const;
  const canGoBack = typeof window !== "undefined" && window.history.length > 1;
  if (canGoBack) return { to: null, label: "Back" } as const;
  return { to: "/", label: "Back to Home" } as const;
});

function goBack() {
  router.back();
}

const stage = ref<"plans" | "payment" | "done">("plans");
const plan = ref<number | null>(null);
const selectedPlan = computed(() => plans.value.find((item) => item.id === plan.value) ?? null);
const isUpgradeCheckout = computed(() => planChangeQuote.value?.type === "upgrade" && Boolean(activeSubscription.value && selectedPlan.value && activeSubscription.value.planId !== selectedPlan.value.id));
const payableMinor = computed(() => promoPreview.value?.total_minor ?? planChangeQuote.value?.total_minor ?? planChangeQuote.value?.subtotal_after_credit_minor ?? selectedPlan.value?.priceMinor ?? 0);

onMounted(async () => {
  if (route.query.payment_status === "success") void finishActivation();
  if (route.query.payment_status === "failed") error.value = "Payment was not completed. You can retry from your invoice.";
  try {
    const catalog = await platformRepository.catalog();
    catalogPlans.value = catalog.plans;
    developmentPaymentBypass.value = Boolean(catalog.developmentPaymentBypass);
    mockPaymentsEnabled.value = Boolean(catalog.mockPaymentsEnabled);
    if (mockPaymentsEnabled.value) provider.value = "mock";
    if (isLoggedIn.value) {
      const [accountResponse, subscriptions] = await Promise.all([platformRepository.account(), platformRepository.subscriptions()]);
      account.value = accountResponse.account;
      activeSubscription.value = subscriptions[0] ?? null;
    }
    plan.value = plans.value[0]?.id ?? null;
  } catch (reason) {
    if (reason instanceof ApiError && reason.status === 401) {
      isLoggedIn.value = false;
    } else {
      error.value = reason instanceof Error ? reason.message : "Plans could not be loaded.";
    }
  } finally {
    loading.value = false;
  }
});

async function chooseAndPay(id: number, action: string) {
  if (!isLoggedIn.value) {
    void router.push({ path: "/signup", query: { redirect: "/pricing" } });
    return;
  }
  if (action === "complete") {
    void router.push({ path: "/billing", query: { reason: "payment_pending" } });
    return;
  }
  if (action === "manage" || action === "current") {
    void router.push({ path: "/settings", query: { tab: "subscription" } });
    return;
  }
  if (action === "change" && activeSubscription.value) {
    submitting.value = true;
    error.value = "";
    planChangeMessage.value = "";
    try {
      const quote = await platformRepository.previewPlanChange(activeSubscription.value.id, id);
      plan.value = id;
      planChangeQuote.value = quote;
      promoPreview.value = null;
      promoCode.value = "";
      promoMessage.value = "";
      if (quote.type === "downgrade") {
        const response = await platformRepository.changePlan(activeSubscription.value.id, id);
        activeSubscription.value = response.subscription;
        planChangeMessage.value = `Downgrade scheduled for ${new Date(quote.effective_at).toLocaleDateString()}. Your current plan continues until then.`;
      } else {
        stage.value = "payment";
      }
    } catch (reason) {
      error.value = reason instanceof Error ? reason.message : "The plan could not be changed.";
    } finally {
      submitting.value = false;
    }
    return;
  }
  plan.value = id;
  stage.value = "payment";
}

function planAction(p: UiPlan) {
  const status = activeSubscription.value?.status || "";
  const samePlan = activeSubscription.value?.planId === p.id;
  const isCurrent = samePlan && ["active", "paused"].includes(status);
  const isPending = samePlan && ["incomplete", "past_due"].includes(status);
  const canChange = !samePlan && ["active", "paused"].includes(status);
  const action: "current" | "manage" | "complete" | "change" | "activate" = isCurrent ? "current" : isPending ? "complete" : canChange ? "change" : activeSubscription.value ? "manage" : "activate";
  const currentPrice = activeSubscription.value?.plan?.priceMinor ?? 0;
  const cta = action === "current"
    ? "Active plan"
    : action === "complete"
      ? "Complete payment"
      : action === "change"
        ? (p.priceMinor > currentPrice ? "Upgrade plan" : "Change plan")
        : action === "manage"
          ? "Manage current plan"
          : "Activate plan";
  const ctaClass =
    action === "current"
      ? "bg-mint-light text-mint border border-[#B6E4C7] cursor-default"
      : action === "manage" || action === "complete"
        ? "bg-bg-soft text-ink hover:bg-bg-soft2 border border-border"
        : p.popular
            ? "bg-blue text-white hover:opacity-90"
            : "bg-bg-soft text-ink hover:bg-bg-soft2 border border-border";
  return { isCurrent, action, cta, ctaClass };
}

function money(p: UiPlan) {
  return formatMoney(p.priceMinor, p.currency);
}

function moneyMinor(minor: number) {
  return formatMoney(minor, selectedPlan.value?.currency || "PKR");
}

async function applyPromo() {
  const profileId = account.value?.profileId;
  if (!profileId || !plan.value || !promoCode.value.trim()) return;
  validatingPromo.value = true; promoMessage.value = ""; promoPreview.value = null;
  try {
    if (isUpgradeCheckout.value && activeSubscription.value) {
      const quote = await platformRepository.previewPlanChange(activeSubscription.value.id, plan.value, promoCode.value.trim());
      planChangeQuote.value = quote;
      promoPreview.value = { code: promoCode.value.trim().toUpperCase(), name: "Promo / discount code", discount_minor: quote.promotion_discount_minor || 0, subtotal_minor: quote.subtotal_after_credit_minor, total_minor: quote.total_minor ?? quote.subtotal_after_credit_minor };
    } else {
      promoPreview.value = await platformRepository.validatePromotion(profileId, plan.value, promoCode.value.trim());
    }
    promoMessage.value = `${promoPreview.value.name} applied. It will be consumed only after successful payment.`;
  }
  catch (reason) { promoMessage.value = reason instanceof Error ? reason.message : "Promo code could not be applied."; }
  finally { validatingPromo.value = false; }
}

async function activatePlan() {
  const studentProfileId = account.value?.profileId;
  if (!studentProfileId || !plan.value) {
    error.value = "A student profile is required before activating a plan.";
    return;
  }
  submitting.value = true;
  error.value = "";
  try {
    const checkout = isUpgradeCheckout.value && activeSubscription.value
      ? await platformRepository.checkoutPlanChange(activeSubscription.value.id, plan.value, provider.value, promoPreview.value?.code || promoCode.value.trim() || undefined, referralCode.value.trim() || undefined)
      : await platformRepository.checkout(studentProfileId, plan.value, provider.value, promoPreview.value?.code || promoCode.value.trim() || undefined, referralCode.value.trim() || undefined);
    activeSubscription.value = checkout.subscription;
    if (!checkout.requires_payment) { await finishActivation(); return; }
    const gateway = checkout.gateway as Record<string, unknown>;
    if (gateway.type === "redirect_form") {
      submitRedirectForm(String(gateway.url), gateway.fields as Record<string, string>);
      return;
    }
    if (gateway.type === "google_pay" && checkout.payment) {
      const paymentToken = await requestGooglePayToken(gateway);
      await platformRepository.captureGooglePay(checkout.payment.id, paymentToken);
      await finishActivation();
      return;
    }
    if (gateway.type === "mock_card" && checkout.payment) {
      const paymentToken = validateMockPayment();
      await platformRepository.captureMockPayment(checkout.payment.id, paymentToken);
      await finishActivation();
    }
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "The plan could not be activated.";
  } finally {
    submitting.value = false;
  }
}

function validateMockPayment(): "mock_success" | "mock_decline" {
  const card = mockCardNumber.value.replace(/\D/g, "");
  if (!/^\d{16}$/.test(card)) throw new Error("Enter a valid 16-digit demo card number.");
  if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(mockExpiry.value.trim())) throw new Error("Enter expiry in MM/YY format.");
  if (!/^\d{3,4}$/.test(mockCvv.value.trim())) throw new Error("Enter a valid demo CVV.");
  if (card === "4000000000000002") return "mock_decline";
  if (card !== "4242424242424242") throw new Error("Use the approved demo card 4242 4242 4242 4242.");
  return "mock_success";
}

async function finishActivation() {
  const allowed = await checkDashboardAccess(true);
  if (!allowed) {
    stage.value = "plans";
    error.value = "The plan has not reached active status yet. Complete payment or refresh after provider confirmation.";
    return;
  }
  stage.value = "done";
  window.setTimeout(() => { void router.push("/dashboard"); }, 1200);
}

function submitRedirectForm(url: string, fields: Record<string, string>) {
  const form = document.createElement("form"); form.method = "POST"; form.action = url;
  Object.entries(fields).forEach(([name, value]) => { const input = document.createElement("input"); input.type = "hidden"; input.name = name; input.value = value; form.appendChild(input); });
  document.body.appendChild(form); form.submit();
}

async function loadGooglePay() {
  const googleWindow = window as unknown as { google?: { payments?: { api?: { PaymentsClient: new (config: Record<string, unknown>) => { loadPaymentData(request: Record<string, unknown>): Promise<{ paymentMethodData: { tokenizationData: { token: string } } }> } } } } };
  if (googleWindow.google?.payments?.api) return googleWindow.google.payments.api;
  await new Promise<void>((resolve, reject) => { const existing = document.querySelector<HTMLScriptElement>('script[data-google-pay]'); if (existing) { existing.addEventListener("load", () => resolve(), { once: true }); return; } const script = document.createElement("script"); script.src = "https://pay.google.com/gp/p/js/pay.js"; script.async = true; script.dataset.googlePay = "true"; script.onload = () => resolve(); script.onerror = () => reject(new Error("Google Pay could not be loaded.")); document.head.appendChild(script); });
  if (!googleWindow.google?.payments?.api) throw new Error("Google Pay is unavailable in this browser.");
  return googleWindow.google.payments.api;
}

async function requestGooglePayToken(gateway: Record<string, unknown>) {
  const api = await loadGooglePay();
  const client = new api.PaymentsClient({ environment: gateway.environment });
  const result = await client.loadPaymentData({ apiVersion: 2, apiVersionMinor: 0, merchantInfo: gateway.merchantInfo, allowedPaymentMethods: gateway.allowedPaymentMethods, transactionInfo: gateway.transactionInfo });
  return result.paymentMethodData.tokenizationData.token;
}
</script>

<template>
  <!-- Done -->
  <div v-if="stage === 'done'" class="min-h-screen bg-bg flex flex-col">
    <TopNav />
    <div class="flex-1 grid place-items-center px-5">
      <div class="bg-bg-card border border-border rounded-2xl p-10 max-w-[440px] w-full text-center reveal-up">
        <div class="w-14 h-14 rounded-full bg-mint-light grid place-items-center mx-auto mb-5">
          <Check :size="28" class="text-mint" />
        </div>
        <h2 class="text-[24px] font-bold text-ink mb-2">Plan activated</h2>
        <p class="text-[14px] text-ink3 font-light mb-7">
          Your {{ selectedPlan?.name }} plan is active. You can manage it anytime in Settings.
        </p>
        <RouterLink to="/dashboard" class="inline-flex items-center gap-2 bg-blue text-white text-[14px] font-semibold px-7 py-3 rounded-md hover:opacity-90">
          Go to dashboard <ArrowRight :size="15" />
        </RouterLink>
      </div>
    </div>
  </div>

  <!-- Payment -->
  <div v-else-if="stage === 'payment'" class="min-h-screen bg-bg flex flex-col">
    <TopNav />
    <div class="flex-1 max-w-[480px] w-full mx-auto px-5 py-10">
      <button class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink3 hover:text-ink mb-5" @click="stage = 'plans'">
        <ArrowLeft :size="14" /> Back to plans
      </button>
      <p class="label-mono mb-2">Confirmation</p>
      <h1 class="text-[28px] font-bold text-ink tracking-[-0.025em] mb-1">{{ isUpgradeCheckout ? 'Upgrade your plan' : 'Activate your plan' }}</h1>
      <p class="text-[14px] text-ink3 mb-7 font-light">
        {{ isUpgradeCheckout ? `A new billing cycle starts after successful payment.` : `${selectedPlan ? money(selectedPlan) : "—"}/${billingIntervalSuffix(selectedPlan?.billingInterval || "month")}` }}
      </p>

      <div class="bg-bg-soft border border-border rounded-xl p-4 mb-5 flex items-center justify-between">
        <div>
          <p class="text-[13px] font-semibold text-ink">{{ selectedPlan?.name }} plan</p>
          <p class="text-[12px] text-ink3">{{ selectedPlan?.scope.replaceAll('_', ' ') }} · {{ billingIntervalLabel(selectedPlan?.billingInterval || "month") }}</p>
        </div>
        <button class="text-[12px] text-blue font-medium" @click="stage = 'plans'">Change</button>
      </div>

      <form class="space-y-3.5 bg-bg-card border border-border rounded-xl p-6" @submit.prevent="activatePlan">
        <p v-if="error" class="rounded-lg bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
        <p v-if="developmentPaymentBypass && provider !== 'mock'" class="rounded-lg border border-gold/30 bg-gold-light px-4 py-3 text-[13px] text-ink2">Development payment bypass is enabled for provider-based checkout. Choose Mock payment to exercise the full demo billing flow.</p>
        <div class="rounded-xl border border-border bg-bg-soft p-4 space-y-3">
          <div><label class="mb-1 block text-[11px] font-semibold text-ink3">Promo / discount code</label><div class="flex gap-2"><input v-model="promoCode" maxlength="80" class="min-w-0 flex-1 rounded-lg border border-border bg-white px-3 py-2.5 text-[13px] uppercase outline-none focus:border-blue" placeholder="e.g. SCHOOL20" @input="promoPreview = null; promoMessage = ''" /><button type="button" :disabled="validatingPromo || !promoCode.trim()" class="rounded-lg border border-border bg-white px-3 text-[12px] font-semibold text-blue disabled:opacity-50" @click="applyPromo">{{ validatingPromo ? 'Checking…' : 'Apply' }}</button></div><p v-if="promoMessage" class="mt-1.5 text-[11px]" :class="promoPreview ? 'text-mint' : 'text-pink'">{{ promoMessage }}</p></div>
          <div><label class="mb-1 block text-[11px] font-semibold text-ink3">Referral code <span class="font-normal text-ink4">(optional)</span></label><input v-model="referralCode" maxlength="32" class="w-full rounded-lg border border-border bg-white px-3 py-2.5 text-[13px] uppercase outline-none focus:border-blue" placeholder="Friend's referral code" /><p class="mt-1 text-[10.5px] text-ink4">The referrer receives their reward promo only after this payment succeeds.</p></div>
          <div v-if="isUpgradeCheckout && planChangeQuote" class="space-y-2 border-t border-border pt-3 text-[12px]">
            <div class="flex justify-between"><span class="text-ink3">New plan</span><strong>{{ moneyMinor(planChangeQuote.target_price_minor) }}</strong></div>
            <div class="flex justify-between"><span class="text-ink3">Unused value from current plan</span><strong class="text-mint">− {{ moneyMinor(planChangeQuote.unused_credit_minor) }}</strong></div>
            <div v-if="promoPreview" class="flex justify-between"><span class="text-ink3">Promo / discount</span><strong class="text-mint">− {{ moneyMinor(promoPreview.discount_minor) }}</strong></div>
            <div class="flex justify-between border-t border-border pt-2"><span class="font-semibold text-ink">Pay now</span><strong class="text-ink">{{ moneyMinor(payableMinor) }}</strong></div>
            <p class="text-[10.5px] leading-relaxed text-ink4">Your previous billing cycle closes only after this payment succeeds. The unused value is carried forward as a separate credit on the new invoice.</p>
          </div>
          <div v-else-if="promoPreview" class="flex items-center justify-between border-t border-border pt-3 text-[12px]"><span class="text-ink3">Discount {{ moneyMinor(promoPreview.discount_minor) }}</span><strong class="text-ink">Pay {{ moneyMinor(promoPreview.total_minor) }}</strong></div>
        </div>
        <div class="grid gap-2" :class="mockPaymentsEnabled ? 'grid-cols-3' : 'grid-cols-2'">
          <label v-if="mockPaymentsEnabled" class="cursor-pointer rounded-xl border p-3 text-[12px] font-semibold" :class="provider === 'mock' ? 'border-blue bg-blue-light text-blue' : 'border-border'"><input v-model="provider" class="mr-2" type="radio" value="mock" />Mock payment</label>
          <label class="cursor-pointer rounded-xl border p-3 text-[12px] font-semibold" :class="provider === 'jazzcash' ? 'border-blue bg-blue-light text-blue' : 'border-border'"><input v-model="provider" class="mr-2" type="radio" value="jazzcash" />JazzCash</label>
          <label class="cursor-pointer rounded-xl border p-3 text-[12px] font-semibold" :class="provider === 'google_pay' ? 'border-blue bg-blue-light text-blue' : 'border-border'"><input v-model="provider" class="mr-2" type="radio" value="google_pay" />Google Pay</label>
        </div>
        <div v-if="provider === 'mock'" class="space-y-3 rounded-xl border border-border bg-bg-soft p-4">
          <div>
            <label class="mb-1 block text-[11px] font-semibold text-ink3">Demo card number</label>
            <input v-model="mockCardNumber" autocomplete="off" inputmode="numeric" class="w-full rounded-lg border border-border bg-white px-3 py-2.5 text-[13px] outline-none focus:border-blue" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="mb-1 block text-[11px] font-semibold text-ink3">Expiry</label><input v-model="mockExpiry" autocomplete="off" class="w-full rounded-lg border border-border bg-white px-3 py-2.5 text-[13px] outline-none focus:border-blue" /></div>
            <div><label class="mb-1 block text-[11px] font-semibold text-ink3">CVV</label><input v-model="mockCvv" autocomplete="off" inputmode="numeric" class="w-full rounded-lg border border-border bg-white px-3 py-2.5 text-[13px] outline-none focus:border-blue" /></div>
          </div>
          <p class="text-[11px] leading-relaxed text-ink3">Demo only. Card details stay in the browser and are never sent to or stored by Preppy.</p>
        </div>
        <p class="text-[13px] leading-relaxed text-ink2">Your subscription becomes active only after the selected payment path succeeds.</p>
        <button :disabled="submitting" type="submit" class="w-full mt-2 flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold py-3 rounded-md hover:opacity-90 disabled:opacity-50">
          {{ submitting ? "Processing…" : provider === 'mock' ? `Pay ${moneyMinor(payableMinor)} (mock)` : developmentPaymentBypass ? (isUpgradeCheckout ? "Upgrade plan (development)" : "Activate plan (development)") : `Pay with ${provider === 'jazzcash' ? 'JazzCash' : 'Google Pay'} · ${moneyMinor(payableMinor)}` }}
        </button>
        <p class="text-[11px] text-ink4 text-center flex items-center justify-center gap-1.5">
          <Lock :size="11" /> Mock checkout is local/demo-only. Real provider payment details remain handled by the provider.
        </p>
      </form>
    </div>
  </div>

  <!-- Plans -->
  <div v-else class="min-h-screen bg-bg flex flex-col">
    <TopNav />
    <main class="flex-1 max-w-[1080px] w-full mx-auto px-5 py-12">
      <div class="mb-6">
        <RouterLink v-if="backLink.to" :to="backLink.to" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink3 hover:text-ink transition">
          <ArrowLeft :size="14" /> {{ backLink.label }}
        </RouterLink>
        <button v-else class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink3 hover:text-ink transition" @click="goBack">
          <ArrowLeft :size="14" /> {{ backLink.label }}
        </button>
      </div>
      <div class="text-center mb-10">
        <p class="label-mono mb-3">Pricing</p>
        <h1 class="text-[36px] md:text-[42px] font-bold text-ink tracking-[-0.03em]">Choose the plan that fits.</h1>
        <p class="text-[15px] text-ink3 mt-3 font-light max-w-[520px] mx-auto">{{ fromGuestDiagnostic ? 'Your diagnostic is saved. These plans match your selected course and subject.' : 'Choose an available payment method to activate access. Manage your plan anytime.' }}</p>
        <div v-if="isLoggedIn && activeSubscription" class="inline-flex items-center gap-2 mt-5 px-3 py-1.5 rounded-full bg-gold-light border border-[#F2DDA6]">
          <Crown :size="12" class="text-gold" />
          <span class="text-[12px] font-semibold text-ink2">
            Current plan: <span class="text-gold">{{ activeSubscription.plan?.name ?? plans.find((p) => p.id === activeSubscription?.planId)?.name }}</span>
          </span>
        </div>
      </div>
      <p v-if="!loading && account && !plans.length" class="mb-6 rounded-xl border border-gold/30 bg-gold-light px-4 py-3 text-center text-[13px] text-ink2">No active plan currently matches your selected course or subjects. Please contact support or update your academic setup.</p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="p in plans"
          :key="p.id"
          :class="`relative bg-bg-card border rounded-2xl p-7 transition ${
            planAction(p).isCurrent ? 'border-mint shadow-soft ring-2 ring-mint/20' : p.popular ? 'border-blue shadow-soft' : 'border-border'
          }`"
        >
          <span v-if="planAction(p).isCurrent" class="absolute -top-3 left-1/2 -translate-x-1/2 text-[10px] font-semibold uppercase tracking-wider bg-mint text-white px-3 py-1 rounded-full">Current plan</span>
          <span v-else-if="p.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 text-[10px] font-semibold uppercase tracking-wider bg-blue text-white px-3 py-1 rounded-full">Most popular</span>
          <p class="text-[13px] font-semibold text-ink2 uppercase tracking-wider">{{ p.name }}</p>
          <div class="flex items-baseline gap-1 mt-3 mb-1">
            <span class="font-mono text-[36px] font-medium text-ink leading-none">{{ money(p) }}</span>
            <span class="text-[13px] text-ink3">/{{ billingIntervalSuffix(p.billingInterval) }}</span>
          </div>
          <p class="text-[12px] text-ink3 mb-5">{{ p.scope.replaceAll('_', ' ') }}</p>
          <ul class="space-y-2.5 mb-7">
            <li v-for="f in p.features" :key="f" class="flex items-start gap-2 text-[13px] text-ink2">
              <Check :size="14" class="text-mint mt-0.5 shrink-0" />
              {{ f }}
            </li>
          </ul>
          <button
            :disabled="submitting || planAction(p).action === 'current'"
            :class="`w-full py-2.5 rounded-md text-[13.5px] font-semibold transition ${planAction(p).ctaClass}`"
            @click="chooseAndPay(p.id, planAction(p).action)"
          >
            {{ planAction(p).cta }}
          </button>
        </div>
      </div>
      <p v-if="loading" class="text-center text-[13px] text-ink3 mt-8">Loading plans…</p>
      <p v-if="planChangeMessage && stage === 'plans'" class="mx-auto mt-8 max-w-[620px] rounded-xl border border-mint/30 bg-mint-light px-4 py-3 text-center text-[13px] text-ink2">{{ planChangeMessage }}</p>
      <p v-if="error && stage === 'plans'" class="text-center text-[13px] text-pink mt-8">{{ error }}</p>
      <p v-if="!isLoggedIn" class="text-center text-[13px] text-ink3 mt-8">
        Already a member? <RouterLink to="/login" class="text-blue font-medium">Sign in</RouterLink>
      </p>
    </main>
    <Footer />
  </div>
</template>