<script setup lang="ts">
import { computed, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import { HelpCircle, MessageSquare, BookOpen, Timer, Brain, BarChart3, CreditCard, UserCircle, ChevronDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";

setPageMeta({
  title: "FAQ — Preppy",
  description: "Find answers to common questions about Preppy, Learn Mode, Exam Mode, AI Feedback, subscriptions, and your learning journey.",
});

const CATEGORIES = [
  { id: "all", label: "All", icon: HelpCircle },
  { id: "general", label: "General", icon: HelpCircle },
  { id: "learn", label: "Learn Mode", icon: BookOpen },
  { id: "exam", label: "Exam Mode", icon: Timer },
  { id: "ai", label: "AI Feedback", icon: Brain },
  { id: "analytics", label: "Results & Analytics", icon: BarChart3 },
  { id: "billing", label: "Subscription & Billing", icon: CreditCard },
  { id: "account", label: "Account & Support", icon: UserCircle },
] as const;

const FAQS = [
  { c: "general", q: "What is Preppy?", a: "Preppy is an AI-powered exam preparation platform that helps students train against official mark schemes and receive examiner-grade feedback." },
  { c: "general", q: "Which exam boards and subjects are supported?", a: "We cover O Level, IGCSE, GCSE, AS and A Level subjects for major exam boards including AQA, Edexcel, CIE and OCR." },
  { c: "learn", q: "How does Learn Mode work?", a: "Learn Mode presents bite-sized questions with progressive hints, instant explanations and model answers so you build understanding step by step." },
  { c: "learn", q: "Can I choose a topic to focus on?", a: "Yes. You can select a subject, topic and difficulty level to create a targeted practice session." },
  { c: "exam", q: "What is the difference between Learn Mode and Exam Mode?", a: "Learn Mode is for guided practice with hints and explanations. Exam Mode simulates real exam conditions with timed papers and no assistance." },
  { c: "exam", q: "Can I pause an exam once it has started?", a: "Exam Mode uses official paper timings to mirror exam-day conditions. Pausing is not allowed to keep the experience authentic." },
  { c: "ai", q: "How does AI Feedback mark my answers?", a: "Our AI compares your answers against official mark schemes, checking keywords, reasoning depth and working shown to award marks accurately." },
  { c: "ai", q: "Will AI Feedback replace my teacher?", a: "No. AI Feedback is designed to complement your teacher by providing instant, detailed feedback between lessons and revision sessions." },
  { c: "analytics", q: "What does the progress dashboard show?", a: "The dashboard tracks your readiness by subject, recent activity, streaks, achievements and personalised improvement tips." },
  { c: "analytics", q: "How often is my progress updated?", a: "Progress updates in real time after every session, so you always see your latest strengths and gaps." },
  { c: "billing", q: "Can I try the platform before subscribing?", a: "Yes. One guest diagnostic session is available per verified email before subscribing." },
  { c: "billing", q: "How do I cancel or change my subscription?", a: "You can manage your subscription from Settings → Subscription at any time. Changes take effect on your next billing cycle." },
  { c: "account", q: "How do I reset my password?", a: "Use the forgot password link on the sign-in page or contact support for assistance." },
  { c: "account", q: "How can I contact the support team?", a: "Visit the Support page to submit a ticket, start a live chat or browse the Help Centre." },
];

const category = ref("all");
const open = ref<number | null>(0);

const filtered = computed(() => FAQS.filter((f) => category.value === "all" || f.c === category.value));

function selectCategory(id: string) {
  category.value = id;
  open.value = null;
}

function onToggle(i: number, e: Event) {
  open.value = (e.currentTarget as HTMLDetailsElement).open ? i : null;
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg">
    <TopNav />

    <main class="flex-1">
      <!-- Hero -->
      <section class="relative border-b border-border overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(60%_50%_at_50%_0%,var(--tw-gradient-stops))] from-blue-light/70 to-transparent opacity-70" />
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 py-16 md:py-24 lg:py-28 text-center">
          <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-bg-card px-3 py-1 text-[11px] font-mono uppercase tracking-[0.14em] text-ink3 mb-5">
            <HelpCircle :size="12" class="text-blue" />
            Help Centre
          </span>
          <h1 class="text-[34px] md:text-[48px] lg:text-[56px] font-bold text-ink tracking-[-0.03em] leading-[1.08]">
            Frequently Asked Questions
          </h1>
          <p class="mt-4 text-[15.5px] md:text-[17px] text-ink3 max-w-[640px] mx-auto leading-relaxed">
            Find answers to the most common questions about AI Star Prep, Learn Mode, Exam Mode,
            AI Feedback, subscriptions, and your learning journey.
          </p>
        </div>
      </section>

      <!-- Category tabs -->
      <section class="py-8 md:py-10 border-b border-border bg-bg-card/50">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="flex flex-wrap gap-2 md:gap-3">
            <button
              v-for="c in CATEGORIES"
              :key="c.id"
              :class="
                cn(
                  'inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-[13px] font-medium transition-colors border',
                  category === c.id ? 'border-blue bg-blue text-white' : 'border-border bg-bg-card text-ink3 hover:text-ink hover:border-blue/40 hover:bg-bg-soft',
                )
              "
              @click="selectCategory(c.id)"
            >
              <component :is="c.icon" :size="15" :stroke-width="2" />
              {{ c.label }}
            </button>
          </div>
        </div>
      </section>

      <!-- FAQ Accordion -->
      <section class="py-12 md:py-16 lg:py-20">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="max-w-[820px] mx-auto divide-y divide-border border border-border rounded-2xl bg-bg-card overflow-hidden">
            <div v-if="filtered.length === 0" class="p-6 text-center text-[14px] text-ink3">No questions found in this category.</div>
            <details
              v-for="(f, i) in filtered"
              :key="`${category}-${i}`"
              :open="open === i"
              class="group"
              @toggle="onToggle(i, $event)"
            >
              <summary class="cursor-pointer list-none flex items-center justify-between gap-4 p-5 md:p-6 hover:bg-bg-soft transition-colors">
                <span class="text-[15.5px] md:text-[16px] font-medium text-ink text-left leading-snug">{{ f.q }}</span>
                <span class="shrink-0 grid place-items-center w-7 h-7 rounded-full border border-border bg-bg group-open:bg-blue group-open:border-blue transition-colors">
                  <ChevronDown :size="15" class="text-ink3 group-open:text-white transition-transform [details[open]>summary>&]:rotate-180" />
                </span>
              </summary>
              <div class="px-5 md:px-6 pb-5 md:pb-6 text-[14px] md:text-[15px] text-ink2 leading-relaxed max-w-[720px]">{{ f.a }}</div>
            </details>
          </div>
        </div>
      </section>

      <!-- Still need help -->
      <section class="py-12 md:py-16 lg:py-20 border-t border-border">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="max-w-[720px] mx-auto text-center bg-bg-card border border-border rounded-2xl p-8 md:p-12">
            <div class="w-12 h-12 rounded-xl bg-violet-light grid place-items-center mx-auto mb-5">
              <MessageSquare :size="22" class="text-violet" />
            </div>
            <h2 class="text-[24px] md:text-[30px] font-bold text-ink tracking-[-0.025em]">Still have questions?</h2>
            <p class="mt-3 text-[15px] text-ink2 max-w-[480px] mx-auto leading-relaxed">
              Our support team is here to help you with anything related to AI Star Prep.
            </p>
            <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
              <RouterLink to="/support" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto text-[14px] font-semibold text-white bg-blue px-6 h-12 rounded-full hover:bg-blue/90 transition-colors">
                Contact Support
              </RouterLink>
              <RouterLink to="/demo" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto text-[14px] font-semibold text-ink2 bg-bg-card border border-border px-6 h-12 rounded-full hover:bg-bg-soft hover:text-ink transition-colors">
                Start Free Demo
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
