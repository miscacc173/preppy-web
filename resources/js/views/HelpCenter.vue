<script setup lang="ts">
import { computed, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { Search, BookOpen, Timer, CreditCard, Receipt, Wrench, Sparkles, ChevronDown, MessageSquare } from "lucide-vue-next";

setPageMeta({ title: "Help Centre — AiStar Prep", description: "Find answers and learn how to get the most from AiStar Prep." });

const CATEGORIES = [
  { id: "start", label: "Getting started", icon: Sparkles, count: 8 },
  { id: "learn", label: "Learn Mode", icon: BookOpen, count: 12 },
  { id: "exam", label: "Exam Mode", icon: Timer, count: 9 },
  { id: "sub", label: "Subscription", icon: CreditCard, count: 6 },
  { id: "billing", label: "Billing", icon: Receipt, count: 5 },
  { id: "tech", label: "Technical issues", icon: Wrench, count: 7 },
];

const FAQS = [
  { c: "start", q: "How do I set my academic level and exam board?", a: "Open Settings → Profile. Choose your level (O Level, IGCSE, GCSE, AS, A Level) and exam board. Your dashboards and recommended practice update immediately." },
  { c: "learn", q: "How is my answer marked?", a: "Each short answer is checked against the official mark scheme — keywords, working shown, and depth of reasoning. MCQs are scored instantly. You always see the A* model answer in the review." },
  { c: "learn", q: "Can I get hints during a session?", a: "Yes. Tap 'Get hint' for progressive hints. Hints never reveal the answer — they nudge you toward the next mark point." },
  { c: "exam", q: "Why can't I choose my own exam timing?", a: "Exam Mode uses official paper timing (e.g. Physics Paper 4 = 2h). This mirrors exam-day conditions exactly." },
  { c: "exam", q: "What happens if I close the tab during an exam?", a: "The timer keeps running. Don't close the tab unless you intend to submit early." },
  { c: "sub", q: "How do I upgrade my plan?", a: "Settings → Subscription. Choose Pro for unlimited subjects, mock exams and analytics." },
  { c: "billing", q: "How do I change my payment method?", a: "Settings → Subscription → Update card. Changes apply to your next billing cycle." },
  { c: "tech", q: "I'm seeing a blank page or load error.", a: "Refresh first. If the problem persists, head to Support and submit a bug report with your browser version." },
];

const q = ref("");
const open = ref<number | null>(0);

const filtered = computed(() =>
  FAQS.filter((f) => !q.value.trim() || f.q.toLowerCase().includes(q.value.toLowerCase()) || f.a.toLowerCase().includes(q.value.toLowerCase())),
);

function onToggle(i: number, e: Event) {
  if ((e.currentTarget as HTMLDetailsElement).open) open.value = i;
}
</script>

<template>
  <AppShell title="Help Centre" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="space-y-6">
      <!-- Hero search -->
      <section class="bg-gradient-to-br from-blue-light to-violet-light/40 border border-border rounded-2xl p-6 lg:p-8">
        <h2 class="text-[24px] lg:text-[28px] font-bold text-ink tracking-[-0.025em]">How can we help?</h2>
        <p class="text-[14px] text-ink2 mt-1">Search guides, FAQs, and tutorials.</p>
        <div class="mt-5 flex items-center gap-2 bg-bg-card border border-border rounded-xl px-4 h-14 max-w-[640px]">
          <Search :size="18" class="text-ink3" />
          <input
            v-model="q"
            placeholder="Search articles…"
            aria-label="Search help articles"
            class="flex-1 bg-transparent outline-none text-[15px] text-ink placeholder:text-ink4"
          />
        </div>
      </section>

      <!-- Categories -->
      <section>
        <h3 class="text-[16px] font-semibold text-ink mb-3">Browse by category</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <button v-for="c in CATEGORIES" :key="c.id" class="text-left bg-bg-card border border-border rounded-2xl p-5 hover:border-blue transition">
            <div class="w-10 h-10 rounded-lg bg-blue-light grid place-items-center mb-3">
              <component :is="c.icon" :size="18" class="text-blue" />
            </div>
            <p class="text-[15px] font-semibold text-ink">{{ c.label }}</p>
            <p class="text-[12px] text-ink3 mt-0.5">{{ c.count }} articles</p>
          </button>
        </div>
      </section>

      <!-- FAQs -->
      <section>
        <h3 class="text-[16px] font-semibold text-ink mb-3">Frequently asked questions</h3>
        <div class="bg-bg-card border border-border rounded-2xl divide-y divide-border overflow-hidden">
          <div v-if="filtered.length === 0" class="p-6 text-center text-[13px] text-ink3">No articles match "{{ q }}".</div>
          <details v-for="(f, i) in filtered" :key="i" :open="open === i" @toggle="onToggle(i, $event)">
            <summary class="cursor-pointer list-none p-5 flex items-center justify-between gap-4 hover:bg-bg-soft min-h-[60px]">
              <span class="text-[14.5px] font-medium text-ink">{{ f.q }}</span>
              <ChevronDown :size="16" class="text-ink3 shrink-0 transition-transform [details[open]>summary>&]:rotate-180" />
            </summary>
            <div class="px-5 pb-5 text-[13.5px] text-ink2 leading-relaxed">{{ f.a }}</div>
          </details>
        </div>
      </section>

      <!-- Contact card -->
      <section class="bg-bg-card border border-border rounded-2xl p-6 flex items-center gap-4 flex-wrap justify-between">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-violet-light grid place-items-center">
            <MessageSquare :size="20" class="text-violet" />
          </div>
          <div>
            <p class="text-[15px] font-semibold text-ink">Still need help?</p>
            <p class="text-[13px] text-ink3">Reach our support team — we typically reply within 4 hours.</p>
          </div>
        </div>
        <RouterLink to="/support" class="inline-flex items-center gap-2 bg-violet text-white text-[14px] font-semibold px-5 h-12 rounded-lg hover:opacity-90">
          Contact support
        </RouterLink>
      </section>
    </div>
  </AppShell>
</template>
