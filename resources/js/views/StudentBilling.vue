<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { ArrowLeft, CreditCard, FileDown, LoaderCircle, ReceiptText, RotateCcw } from "lucide-vue-next";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import DataPanel from "@/components/operations/DataPanel.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import { platformRepository } from "@/data/repositories/platformRepository";
import { canRetryInvoice, outstandingAmount } from "@/data/billing.js";
import type { UiInvoiceRecord, UiPaymentRecord, UiRefundRecord } from "@/data/models/operations";

const route = useRoute();
const invoices = ref<UiInvoiceRecord[]>([]); const payments = ref<UiPaymentRecord[]>([]); const refunds = ref<UiRefundRecord[]>([]);
const loading = ref(true); const payingId = ref<number | null>(null); const downloading = ref("");
const error = ref(route.query.reason === "subscription_required" ? "An active subscription is required to use Learn and Exam mode. Pay an outstanding invoice or select a plan." : "");
const provider = ref<"jazzcash" | "google_pay" | "mock">("jazzcash");
const mockPaymentsEnabled = ref(false);
const mockCardNumber = ref("4242 4242 4242 4242");
const mockExpiry = ref("12/30");
const mockCvv = ref("123");
const money = (minor: number, currency: string) => new Intl.NumberFormat(undefined, { style: "currency", currency }).format(minor / 100);

async function load() {
  loading.value = true;
  try {
    const [invoicePage, paymentPage, refundPage] = await Promise.all([operationsRepository.invoices({ per_page: 100 }), operationsRepository.payments({ per_page: 100 }), operationsRepository.refunds({ per_page: 100 })]);
    invoices.value = invoicePage.items; payments.value = paymentPage.items; refunds.value = refundPage.items;
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load billing history."; }
  finally { loading.value = false; }
}

function submitForm(url: string, fields: Record<string, string>) {
  const form = document.createElement("form"); form.method = "POST"; form.action = url;
  Object.entries(fields).forEach(([name, value]) => { const input = document.createElement("input"); input.type = "hidden"; input.name = name; input.value = value; form.appendChild(input); });
  document.body.appendChild(form); form.submit();
}

async function googleToken(gateway: Record<string, unknown>) {
  const googleWindow = window as unknown as { google?: { payments?: { api?: { PaymentsClient: new (config: Record<string, unknown>) => { loadPaymentData(request: Record<string, unknown>): Promise<{ paymentMethodData: { tokenizationData: { token: string } } }> } } } } };
  if (!googleWindow.google?.payments?.api) await new Promise<void>((resolve, reject) => { const script = document.createElement("script"); script.src = "https://pay.google.com/gp/p/js/pay.js"; script.async = true; script.onload = () => resolve(); script.onerror = () => reject(new Error("Google Pay could not be loaded.")); document.head.appendChild(script); });
  if (!googleWindow.google?.payments?.api) throw new Error("Google Pay is unavailable.");
  const client = new googleWindow.google.payments.api.PaymentsClient({ environment: gateway.environment });
  const result = await client.loadPaymentData({ apiVersion: 2, apiVersionMinor: 0, merchantInfo: gateway.merchantInfo, allowedPaymentMethods: gateway.allowedPaymentMethods, transactionInfo: gateway.transactionInfo });
  return result.paymentMethodData.tokenizationData.token;
}

function mockPaymentToken(): "mock_success" | "mock_decline" {
  const card = mockCardNumber.value.replace(/\D/g, "");
  if (!/^\d{16}$/.test(card)) throw new Error("Enter a valid 16-digit demo card number.");
  if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(mockExpiry.value.trim())) throw new Error("Enter expiry in MM/YY format.");
  if (!/^\d{3,4}$/.test(mockCvv.value.trim())) throw new Error("Enter a valid demo CVV.");
  if (card === "4000000000000002") return "mock_decline";
  if (card !== "4242424242424242") throw new Error("Use the approved demo card 4242 4242 4242 4242.");
  return "mock_success";
}

async function pay(invoice: UiInvoiceRecord) {
  payingId.value = invoice.id; error.value = "";
  try {
    const response = (await operationsRepository.checkoutInvoice(invoice.id, provider.value)).data; const gateway = response.gateway;
    if (gateway.type === "completed") { await load(); return; }
    if (gateway.type === "redirect_form") { submitForm(String(gateway.url), gateway.fields as Record<string, string>); return; }
    if (gateway.type === "mock_card") { await operationsRepository.captureMockPayment(response.payment.id, mockPaymentToken()); await load(); return; }
    const token = await googleToken(gateway); await operationsRepository.captureGooglePay(response.payment.id, token); await load();
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Payment could not be completed."; }
  finally { payingId.value = null; }
}

async function download(key: string, action: () => Promise<void>) {
  downloading.value = key; error.value = "";
  try { await action(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Document download failed."; }
  finally { downloading.value = ""; }
}

onMounted(async () => {
  try {
    const catalog = await platformRepository.catalog();
    mockPaymentsEnabled.value = Boolean(catalog.mockPaymentsEnabled);
    if (mockPaymentsEnabled.value) provider.value = "mock";
  } catch {
    mockPaymentsEnabled.value = false;
  }
  await load();
});
</script>

<template>
  <div class="flex min-h-screen flex-col bg-bg"><TopNav /><main class="mx-auto w-full max-w-[1040px] flex-1 px-5 py-10">
    <RouterLink to="/settings" class="inline-flex items-center gap-1.5 text-[13px] text-ink3"><ArrowLeft :size="14" /> Settings</RouterLink>
    <div class="mt-5 flex flex-wrap items-end justify-between gap-4"><div><p class="label-mono">Platform billing</p><h1 class="mt-1 text-[30px] font-bold">Invoices, payments and receipts</h1><p class="mt-1 text-[13px] text-ink3">Successful payments activate access. Failed attempts can be retried from the original invoice.</p></div><div class="flex rounded-xl border border-border bg-white p-1"><label v-if="mockPaymentsEnabled" class="cursor-pointer rounded-lg px-3 py-2 text-[11px] font-semibold" :class="provider === 'mock' ? 'bg-blue text-white' : ''"><input v-model="provider" class="sr-only" type="radio" value="mock" />Mock</label><label class="cursor-pointer rounded-lg px-3 py-2 text-[11px] font-semibold" :class="provider === 'jazzcash' ? 'bg-blue text-white' : ''"><input v-model="provider" class="sr-only" type="radio" value="jazzcash" />JazzCash</label><label class="cursor-pointer rounded-lg px-3 py-2 text-[11px] font-semibold" :class="provider === 'google_pay' ? 'bg-blue text-white' : ''"><input v-model="provider" class="sr-only" type="radio" value="google_pay" />Google Pay</label></div></div>
    <div v-if="provider === 'mock'" class="mt-4 grid gap-3 rounded-xl border border-border bg-bg-soft p-4 md:grid-cols-[2fr_1fr_1fr]"><div><label class="mb-1 block text-[11px] font-semibold text-ink3">Demo card</label><input v-model="mockCardNumber" class="w-full rounded-lg border border-border bg-white px-3 py-2 text-[12px]" /></div><div><label class="mb-1 block text-[11px] font-semibold text-ink3">Expiry</label><input v-model="mockExpiry" class="w-full rounded-lg border border-border bg-white px-3 py-2 text-[12px]" /></div><div><label class="mb-1 block text-[11px] font-semibold text-ink3">CVV</label><input v-model="mockCvv" class="w-full rounded-lg border border-border bg-white px-3 py-2 text-[12px]" /></div><p class="md:col-span-3 text-[10px] text-ink3">Demo-only values remain in the browser and are never sent or stored.</p></div>
    <p v-if="error" class="mt-5 rounded-xl bg-pink-light p-3 text-[12px] text-pink">{{ error }}</p>
    <div v-if="loading" class="mt-6 grid place-items-center rounded-2xl border border-border bg-white py-20"><LoaderCircle class="animate-spin text-blue" /></div>
    <template v-else>
      <DataPanel class="mt-6" title="Invoices" :description="`${invoices.length} billing periods`" :empty="!invoices.length"><div class="divide-y divide-border"><article v-for="invoice in invoices" :key="invoice.id" class="flex flex-wrap items-center justify-between gap-4 p-5"><div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-light text-blue"><CreditCard :size="18" /></div><div><p class="font-mono text-[12px] font-bold">{{ invoice.number }}</p><p class="text-[11px] text-ink3">Due {{ invoice.dueAt ? new Date(invoice.dueAt).toLocaleDateString() : 'on receipt' }}<span v-if="invoice.retryCount"> · retry {{ invoice.retryCount }}</span></p></div></div><div><p class="text-[16px] font-bold">{{ money(outstandingAmount(invoice), invoice.currency) }}</p><StatusBadge :value="invoice.status" /></div><div class="flex gap-2"><button class="ops-button-secondary inline-flex items-center gap-2" :disabled="downloading === `invoice-${invoice.id}`" @click="download(`invoice-${invoice.id}`, () => operationsRepository.downloadInvoice(invoice.id, invoice.number))"><FileDown :size="14" /> Invoice</button><button v-if="canRetryInvoice(invoice)" :disabled="payingId === invoice.id" class="ops-button-primary inline-flex items-center gap-2" @click="pay(invoice)"><LoaderCircle v-if="payingId === invoice.id" :size="14" class="animate-spin" /><RotateCcw v-else :size="14" />{{ payingId === invoice.id ? 'Processing…' : provider === 'mock' ? 'Pay with Mock' : `Pay with ${provider === 'jazzcash' ? 'JazzCash' : 'Google Pay'}` }}</button></div></article></div></DataPanel>
      <DataPanel class="mt-6" title="Payment history" :description="`${payments.length} attempts`" :empty="!payments.length"><div class="overflow-x-auto"><table class="w-full min-w-[820px] text-left text-[12px]"><thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3">Invoice</th><th class="px-4 py-3">Provider</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Refunded</th><th class="px-4 py-3">Status</th><th class="px-5 py-3 text-right">Documents</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="payment in payments" :key="payment.id"><td class="px-5 py-3.5 font-mono">{{ payment.invoiceNumber || `#${payment.invoiceId}` }}</td><td class="px-4 py-3.5 capitalize">{{ payment.provider.replaceAll('_', ' ') }}<p class="text-[10px] text-ink3">Attempt {{ payment.attemptNumber }}</p></td><td class="px-4 py-3.5 font-semibold">{{ money(payment.amountMinor, payment.currency) }}</td><td class="px-4 py-3.5">{{ money(payment.refundedMinor, payment.currency) }}</td><td class="px-4 py-3.5"><StatusBadge :value="payment.status" /><p v-if="payment.failureMessage" class="mt-1 max-w-[220px] text-[10px] text-pink">{{ payment.failureMessage }}</p></td><td class="px-5 py-3.5 text-right"><button v-if="payment.receiptNumber" class="ops-button-secondary inline-flex items-center gap-1.5" @click="download(`receipt-${payment.id}`, () => operationsRepository.downloadReceipt(payment.id, payment.receiptNumber))"><ReceiptText :size="13" /> Receipt</button></td></tr></tbody></table></div></DataPanel>
      <DataPanel class="mt-6" title="Refund history" :description="`${refunds.length} refunds`" :empty="!refunds.length"><div class="grid gap-3 p-5 md:grid-cols-2"><article v-for="refund in refunds" :key="refund.id" class="rounded-xl border border-border p-4"><div class="flex items-start justify-between"><div><p class="font-semibold">{{ money(refund.amountMinor, refund.currency) }}</p><p class="mt-1 text-[11px] text-ink3">{{ refund.reason }}</p></div><StatusBadge :value="refund.status" /></div><button v-if="refund.status === 'succeeded'" class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-blue" @click="download(`refund-${refund.id}`, () => operationsRepository.downloadRefundReceipt(refund.id))"><FileDown :size="13" /> Refund receipt</button></article></div></DataPanel>
    </template>
  </main><Footer /></div>
</template>
