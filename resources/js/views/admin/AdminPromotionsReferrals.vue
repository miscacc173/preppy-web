<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { Download, LoaderCircle, Plus, RefreshCw, Search, ToggleLeft, ToggleRight, X } from "lucide-vue-next";
import DataPanel from "@/components/operations/DataPanel.vue";
import OpsModal from "@/components/operations/OpsModal.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import type { UiCatalogRecord, UiInstituteRecord } from "@/data/models/operations";

type View = "promos" | "referrals" | "redemptions";
type Promo = { id:number; institute_id:number; institute_name:string; name:string; code:string; discount_type:string; amount_minor:number|null; percentage:number|null; starts_at:string|null; ends_at:string|null; max_redemptions:number|null; per_user_limit:number; redemptions_count:number; is_active:boolean; plans:Array<{id:number;name:string;code:string}>; successful_redemptions:number; pending_redemptions:number; unique_users:number; total_discount_minor:number; created_at:string|null };
type Referral = { id:number; status:string; referrer_name:string; referrer_email:string; referred_name:string; referred_email:string; referral_code:string; reward_code?:string|null; reward_discount_type?:string|null; reward_amount_minor?:number|null; reward_percentage?:number|null; reward_ends_at?:string|null; reward_redemptions_count?:number|null; reward_is_active?:boolean|null; payment_status?:string|null; payment_amount_minor?:number|null; payment_currency?:string|null; payment_provider?:string|null; institute_name?:string|null; created_at:string|null };
type Redemption = { id:number; promotion_type:string; code:string; user_name:string; user_email:string; invoice_id:number|null; payment_id:number|null; discount_minor:number; status:string; institute_name?:string|null; consumed_at?:string|null; created_at:string|null };
type Summary = { institute_promos:number; active_institute_promos:number; successful_promo_redemptions:number; promo_discount_minor:number; referrals:number; qualified_referrals:number; referral_rewards_issued:number; referral_rewards_used:number };

const view = ref<View>("promos");
const loading = ref(false); const exporting = ref(false); const savingPromo = ref(false); const showPromoCreate = ref(false); const error = ref(""); const busyPromoId = ref<number|null>(null);
const institutes = ref<UiInstituteRecord[]>([]); const plans = ref<UiCatalogRecord[]>([]); const promos = ref<Promo[]>([]); const referrals = ref<Referral[]>([]); const redemptions = ref<Redemption[]>([]);
const summary = ref<Summary>({ institute_promos:0, active_institute_promos:0, successful_promo_redemptions:0, promo_discount_minor:0, referrals:0, qualified_referrals:0, referral_rewards_issued:0, referral_rewards_used:0 });
const filters = reactive({ institute_id: 0, status: "", code: "", student: "", date_from: "", date_to: "" });
const promoForm = reactive({ institute_id: 0, name: "", code: "", discount_type: "percentage", percentage: 10, amount: 0, max_redemptions: 100 as number | null, per_user_limit: 1, starts_at: "", ends_at: "", plan_ids: [] as number[], is_active: true });
const money = (minor:number, currency="GBP") => new Intl.NumberFormat(undefined,{style:"currency",currency}).format((minor || 0)/100);
const date = (value?:string|null) => value ? new Date(value).toLocaleDateString() : "—";
const value = (row:Promo) => row.discount_type === "percentage" ? `${Number(row.percentage || 0)}%` : money(row.amount_minor || 0);
const rewardValue = (row:Referral) => row.reward_discount_type === "percentage" ? `${Number(row.reward_percentage || 0)}%` : row.reward_amount_minor ? money(row.reward_amount_minor) : "—";
const activeFilters = computed(() => ({ institute_id: filters.institute_id || null, status: filters.status || null, code: filters.code || null, student: filters.student || null, date_from: filters.date_from || null, date_to: filters.date_to || null }));

async function load() {
  loading.value = true; error.value = "";
  try {
    const [overview, institutePage, planPage] = await Promise.all([operationsRepository.adminPromotionsReferrals(activeFilters.value), operationsRepository.institutes({ per_page: 100 }), operationsRepository.plans({ per_page: 100 })]);
    const data = overview.data as any;
    summary.value = data.summary || summary.value; promos.value = data.promoRows || []; referrals.value = data.referrals || []; redemptions.value = data.redemptions || []; institutes.value = institutePage.items; plans.value = planPage.items;
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load promotions and referrals."; }
  finally { loading.value = false; }
}
function clearFilters() { Object.assign(filters,{ institute_id:0,status:"",code:"",student:"",date_from:"",date_to:"" }); void load(); }
async function togglePromo(row:Promo) { busyPromoId.value = row.id; error.value = ""; try { await operationsRepository.updateAdminInstitutePromoStatus(row.id,!row.is_active); await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to update promo status."; } finally { busyPromoId.value = null; } }
async function exportCsv() { exporting.value = true; error.value = ""; try { await operationsRepository.exportAdminPromotionsReferrals(activeFilters.value); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to export report."; } finally { exporting.value = false; } }
function resetPromoForm() { Object.assign(promoForm, { institute_id:0, name:"", code:"", discount_type:"percentage", percentage:10, amount:0, max_redemptions:100, per_user_limit:1, starts_at:"", ends_at:"", plan_ids:[], is_active:true }); }
async function createPromo() {
  if (!promoForm.institute_id) { error.value = "Select the institute that owns this promotion."; return; }
  savingPromo.value = true; error.value = "";
  try {
    await operationsRepository.createInstitutePromoCode(promoForm.institute_id, {
      name: promoForm.name, code: promoForm.code.toUpperCase(), discount_type: promoForm.discount_type,
      amount_minor: promoForm.discount_type === "fixed" ? Math.round(Number(promoForm.amount) * 100) : null,
      percentage: promoForm.discount_type === "percentage" ? Number(promoForm.percentage) : null,
      max_redemptions: promoForm.max_redemptions || null, per_user_limit: promoForm.per_user_limit,
      starts_at: promoForm.starts_at || null, ends_at: promoForm.ends_at || null,
      plan_ids: [...promoForm.plan_ids], is_active: promoForm.is_active,
    });
    showPromoCreate.value = false; resetPromoForm(); await load();
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to create promotion."; }
  finally { savingPromo.value = false; }
}

onMounted(load);
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h3 class="text-[18px] font-bold">Promotions & referrals</h3><p class="mt-1 text-[12px] text-ink3">Platform-wide oversight of institute promo codes, referral rewards and successful discount redemptions.</p></div>
      <div class="flex flex-wrap gap-2"><button class="ops-button-primary inline-flex items-center gap-2" @click="showPromoCreate = true"><Plus :size="14"/>Create promotion</button><button class="ops-button-secondary inline-flex items-center gap-2" :disabled="loading" @click="load"><RefreshCw :size="14" :class="loading ? 'animate-spin' : ''"/>Refresh</button><button class="ops-button-secondary inline-flex items-center gap-2" :disabled="exporting" @click="exportCsv"><LoaderCircle v-if="exporting" :size="14" class="animate-spin"/><Download v-else :size="14"/>Export CSV</button></div>
    </div>
    <p v-if="error" class="rounded-xl bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl border border-border bg-bg-card p-4"><p class="text-[11px] font-semibold uppercase tracking-wide text-ink3">Institute promos</p><p class="mt-2 text-[24px] font-bold">{{ summary.active_institute_promos }} / {{ summary.institute_promos }}</p><p class="text-[11px] text-ink3">active / total</p></div>
      <div class="rounded-2xl border border-border bg-bg-card p-4"><p class="text-[11px] font-semibold uppercase tracking-wide text-ink3">Successful redemptions</p><p class="mt-2 text-[24px] font-bold">{{ summary.successful_promo_redemptions }}</p><p class="text-[11px] text-ink3">{{ money(summary.promo_discount_minor) }} total discount</p></div>
      <div class="rounded-2xl border border-border bg-bg-card p-4"><p class="text-[11px] font-semibold uppercase tracking-wide text-ink3">Referrals</p><p class="mt-2 text-[24px] font-bold">{{ summary.referrals }}</p><p class="text-[11px] text-ink3">{{ summary.qualified_referrals }} qualified</p></div>
      <div class="rounded-2xl border border-border bg-bg-card p-4"><p class="text-[11px] font-semibold uppercase tracking-wide text-ink3">Referral rewards</p><p class="mt-2 text-[24px] font-bold">{{ summary.referral_rewards_issued }}</p><p class="text-[11px] text-ink3">{{ summary.referral_rewards_used }} used</p></div>
    </div>

    <div class="rounded-2xl border border-border bg-bg-card p-4">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <label class="text-[11px] font-semibold">Institute<select v-model.number="filters.institute_id" class="ops-input"><option :value="0">All institutes</option><option v-for="item in institutes" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
        <label class="text-[11px] font-semibold">Status<select v-model="filters.status" class="ops-input"><option value="">All statuses</option><option value="active">Active promos</option><option value="inactive">Inactive promos</option><option value="pending">Pending</option><option value="consumed">Consumed</option><option value="qualified">Qualified</option><option value="rewarded">Rewarded</option><option value="cancelled">Cancelled</option></select></label>
        <label class="text-[11px] font-semibold">Code<input v-model="filters.code" class="ops-input uppercase" placeholder="SCHOOL20"/></label>
        <label class="text-[11px] font-semibold">Student<input v-model="filters.student" class="ops-input" placeholder="Name or email"/></label>
        <label class="text-[11px] font-semibold">From<input v-model="filters.date_from" type="date" class="ops-input"/></label>
        <label class="text-[11px] font-semibold">To<input v-model="filters.date_to" type="date" class="ops-input"/></label>
      </div>
      <div class="mt-3 flex justify-end gap-2"><button class="ops-button-secondary" @click="clearFilters">Clear</button><button class="ops-button-primary inline-flex items-center gap-2" @click="load"><Search :size="14"/>Apply filters</button></div>
    </div>

    <div class="flex gap-1 rounded-xl border border-border bg-bg-card p-1.5"><button v-for="item in [{key:'promos',label:'Institute promo codes'},{key:'referrals',label:'Referrals & rewards'},{key:'redemptions',label:'Redemptions'}]" :key="item.key" class="rounded-lg px-4 py-2 text-[12px] font-semibold transition" :class="view === item.key ? 'bg-blue text-white shadow-sm' : 'text-ink3 hover:bg-bg-soft'" @click="view = item.key as View">{{ item.label }}</button></div>

    <DataPanel v-if="view === 'promos'" title="Institute promo codes" :description="`${promos.length} codes`" :loading="loading" :empty="!promos.length">
      <div class="overflow-x-auto"><table class="w-full min-w-[1180px] text-left text-[12px]"><thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3">Code</th><th class="px-4 py-3">Institute</th><th class="px-4 py-3">Value</th><th class="px-4 py-3">Eligible plans</th><th class="px-4 py-3">Usage</th><th class="px-4 py-3">Validity</th><th class="px-4 py-3">Discount given</th><th class="px-4 py-3">Status</th><th class="px-5 py-3 text-right">Control</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="row in promos" :key="row.id" class="hover:bg-bg-soft/70"><td class="px-5 py-3.5"><p class="font-mono font-bold">{{ row.code }}</p><p class="text-[10px] text-ink3">{{ row.name }}</p></td><td class="px-4 py-3.5 font-semibold">{{ row.institute_name }}</td><td class="px-4 py-3.5 font-semibold">{{ value(row) }}</td><td class="max-w-[240px] px-4 py-3.5 text-ink2">{{ row.plans.length ? row.plans.map(p=>p.name).join(', ') : 'All eligible plans' }}</td><td class="px-4 py-3.5"><p>{{ row.successful_redemptions }} successful · {{ row.pending_redemptions }} pending</p><p class="text-[10px] text-ink3">{{ row.unique_users }} students · limit {{ row.max_redemptions ?? 'unlimited' }} · {{ row.per_user_limit }}/student</p></td><td class="px-4 py-3.5 text-ink3">{{ date(row.starts_at) }} – {{ date(row.ends_at) }}</td><td class="px-4 py-3.5 font-semibold">{{ money(row.total_discount_minor) }}</td><td class="px-4 py-3.5"><StatusBadge :value="row.is_active ? 'active' : 'inactive'"/></td><td class="px-5 py-3.5 text-right"><button class="ops-icon-button" :disabled="busyPromoId === row.id" :title="row.is_active ? 'Deactivate promo' : 'Activate promo'" @click="togglePromo(row)"><LoaderCircle v-if="busyPromoId === row.id" :size="15" class="animate-spin"/><ToggleRight v-else-if="row.is_active" :size="17"/><ToggleLeft v-else :size="17"/></button></td></tr></tbody></table></div>
    </DataPanel>

    <div v-else-if="view === 'referrals'" class="space-y-3"><p class="rounded-xl border border-border bg-bg-soft px-4 py-3 text-[12px] text-ink3">Referrals are created automatically when a student uses another student’s referral code. Reward promo codes are issued only after the referred student completes a successful or mock-success payment.</p><DataPanel title="Referrals & rewards" :description="`${referrals.length} referrals`" :loading="loading" :empty="!referrals.length">
      <div class="overflow-x-auto"><table class="w-full min-w-[1200px] text-left text-[12px]"><thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3">Referral code</th><th class="px-4 py-3">Referrer</th><th class="px-4 py-3">Referred student</th><th class="px-4 py-3">Institute</th><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Reward promo</th><th class="px-4 py-3">Reward status</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="row in referrals" :key="row.id" class="hover:bg-bg-soft/70"><td class="px-5 py-3.5 font-mono font-bold">{{ row.referral_code }}</td><td class="px-4 py-3.5"><p class="font-semibold">{{ row.referrer_name }}</p><p class="text-[10px] text-ink3">{{ row.referrer_email }}</p></td><td class="px-4 py-3.5"><p class="font-semibold">{{ row.referred_name }}</p><p class="text-[10px] text-ink3">{{ row.referred_email }}</p></td><td class="px-4 py-3.5">{{ row.institute_name || 'Platform' }}</td><td class="px-4 py-3.5"><p class="capitalize">{{ row.payment_provider?.replaceAll('_',' ') || '—' }} · {{ row.payment_status || '—' }}</p><p v-if="row.payment_amount_minor" class="text-[10px] text-ink3">{{ money(row.payment_amount_minor,row.payment_currency || 'GBP') }}</p></td><td class="px-4 py-3.5"><p class="font-mono font-semibold">{{ row.reward_code || 'Not issued' }}</p><p class="text-[10px] text-ink3">{{ rewardValue(row) }} · expires {{ date(row.reward_ends_at) }}</p></td><td class="px-4 py-3.5"><StatusBadge :value="row.reward_code ? ((row.reward_redemptions_count || 0) > 0 ? 'used' : row.reward_is_active ? 'unused' : 'expired') : 'not-issued'"/></td><td class="px-4 py-3.5"><StatusBadge :value="row.status"/></td><td class="px-4 py-3.5 text-ink3">{{ date(row.created_at) }}</td></tr></tbody></table></div>
    </DataPanel></div>

    <div v-else class="space-y-3"><p class="rounded-xl border border-border bg-bg-soft px-4 py-3 text-[12px] text-ink3">Redemptions are financial audit records and are never created manually. They are generated only when a promo/discount is reserved during checkout and finalized after a successful or mock-success payment.</p><DataPanel title="Promotion redemptions" :description="`${redemptions.length} latest records`" :loading="loading" :empty="!redemptions.length">
      <div class="overflow-x-auto"><table class="w-full min-w-[1050px] text-left text-[12px]"><thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3">Code</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Institute</th><th class="px-4 py-3">Discount</th><th class="px-4 py-3">Invoice</th><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Consumed</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="row in redemptions" :key="row.id" class="hover:bg-bg-soft/70"><td class="px-5 py-3.5 font-mono font-bold">{{ row.code }}</td><td class="px-4 py-3.5 capitalize">{{ row.promotion_type.replaceAll('_',' ') }}</td><td class="px-4 py-3.5"><p class="font-semibold">{{ row.user_name }}</p><p class="text-[10px] text-ink3">{{ row.user_email }}</p></td><td class="px-4 py-3.5">{{ row.institute_name || 'Platform' }}</td><td class="px-4 py-3.5 font-semibold">{{ money(row.discount_minor) }}</td><td class="px-4 py-3.5">{{ row.invoice_id ? `#${row.invoice_id}` : '—' }}</td><td class="px-4 py-3.5">{{ row.payment_id ? `#${row.payment_id}` : 'Pending' }}</td><td class="px-4 py-3.5"><StatusBadge :value="row.status"/></td><td class="px-4 py-3.5 text-ink3">{{ date(row.consumed_at) }}</td></tr></tbody></table></div>
    </DataPanel></div>

    <OpsModal :open="showPromoCreate" max-width="max-w-2xl" :close-on-backdrop="!savingPromo" @close="showPromoCreate = false">
      <form class="rounded-2xl border border-border bg-bg-card p-5 shadow-2xl" @submit.prevent="createPromo">
        <div class="mb-5 flex items-start justify-between gap-3"><div><h3 class="text-[17px] font-bold">Create institute promotion</h3><p class="mt-1 text-[12px] text-ink3">Admin creates the code on behalf of the selected institute. Referrals and redemption records remain automatic.</p></div><button type="button" :disabled="savingPromo" @click="showPromoCreate=false"><X :size="18"/></button></div>
        <div class="grid gap-3 md:grid-cols-2">
          <label class="text-[11px] font-semibold">Institute<select v-model.number="promoForm.institute_id" required class="ops-input"><option :value="0" disabled>Select institute</option><option v-for="item in institutes" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
          <label class="text-[11px] font-semibold">Promotion name<input v-model="promoForm.name" required class="ops-input" placeholder="School autumn offer"/></label>
          <label class="text-[11px] font-semibold">Code<input v-model="promoForm.code" required maxlength="80" class="ops-input uppercase" placeholder="SCHOOL20"/></label>
          <label class="text-[11px] font-semibold">Discount type<select v-model="promoForm.discount_type" class="ops-input"><option value="percentage">Percentage</option><option value="fixed">Fixed amount</option></select></label>
          <label v-if="promoForm.discount_type === 'percentage'" class="text-[11px] font-semibold">Percentage<input v-model.number="promoForm.percentage" type="number" min="0.01" max="99.99" step="0.01" class="ops-input"/></label>
          <label v-else class="text-[11px] font-semibold">Fixed amount<input v-model.number="promoForm.amount" type="number" min="0.01" step="0.01" class="ops-input"/><span class="mt-1 block font-normal text-ink4">Applied in the invoice currency.</span></label>
          <label class="text-[11px] font-semibold">Total usage limit<input v-model.number="promoForm.max_redemptions" type="number" min="1" class="ops-input"/></label>
          <label class="text-[11px] font-semibold">Per-student limit<input v-model.number="promoForm.per_user_limit" type="number" min="1" class="ops-input"/></label>
          <label class="text-[11px] font-semibold">Starts<input v-model="promoForm.starts_at" type="datetime-local" class="ops-input"/></label>
          <label class="text-[11px] font-semibold">Ends<input v-model="promoForm.ends_at" type="datetime-local" class="ops-input"/></label>
          <label class="md:col-span-2 text-[11px] font-semibold">Eligible plans<select v-model="promoForm.plan_ids" multiple class="ops-input min-h-28"><option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option></select><span class="mt-1 block font-normal text-ink4">Leave empty to allow all plans otherwise eligible for students of the selected institute.</span></label>
        </div>
        <div class="mt-5 flex justify-end gap-2"><button type="button" class="ops-button-secondary" :disabled="savingPromo" @click="showPromoCreate=false">Cancel</button><button class="ops-button-primary inline-flex min-w-32 items-center justify-center gap-2" :disabled="savingPromo || !promoForm.institute_id"><LoaderCircle v-if="savingPromo" :size="14" class="animate-spin"/>{{ savingPromo ? 'Creating…' : 'Create promotion' }}</button></div>
      </form>
    </OpsModal>
  </div>
</template>
