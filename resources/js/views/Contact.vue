<script setup lang="ts">
import { reactive, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import {
  MessageSquare, UserCircle, CreditCard, HelpCircle, BookOpen, DollarSign,
  Send, CheckCircle2, AlertCircle, Loader2, ArrowRight,
} from "lucide-vue-next";
import { cn } from "@/lib/utils";
import Field from "./contact/Field.vue";
import { platformRepository } from "@/data/repositories/platformRepository";

setPageMeta({
  title: "Contact — Preppy",
  description: "Have a question about Preppy, Learn Mode, Exam Mode, your account or subscription? Send us a message and our support team will help you.",
});

const SUPPORT_OPTIONS = [
  { icon: MessageSquare, title: "General Support", desc: "For general questions about AI Star Prep and the learning experience.", tone: "blue" as const },
  { icon: UserCircle, title: "Account Support", desc: "For login, profile, account access, and technical issues.", tone: "violet" as const },
  { icon: CreditCard, title: "Subscription & Billing", desc: "For questions related to plans, payments, subscriptions, and billing.", tone: "amber" as const },
];

const CATEGORIES = ["General Question", "Learn Mode", "Exam Mode", "Results & Analytics", "Account Support", "Subscription & Billing", "Technical Issue", "Feedback"] as const;

const TIPS = [
  { icon: HelpCircle, title: "Check the basics first", desc: "Most questions are already answered on the FAQ page." },
  { icon: UserCircle, title: "Include your account email", desc: "So we can look up your subscription and session history." },
  { icon: BookOpen, title: "Tell us the mode", desc: "Mention whether it happened in Learn Mode or Exam Mode." },
];

const RESOURCES = [
  { to: "/faq", icon: HelpCircle, title: "Visit FAQs", desc: "Answers to the most common questions.", tone: "blue" as const },
  { to: "/features", icon: BookOpen, title: "Explore Features", desc: "See what AI Star Prep can do for you.", tone: "violet" as const },
  { to: "/pricing", icon: DollarSign, title: "View Pricing", desc: "Compare plans and choose what fits.", tone: "amber" as const },
];

const TONE: Record<"blue" | "violet" | "amber", { bg: string; fg: string }> = {
  blue: { bg: "bg-blue-light", fg: "text-blue" },
  violet: { bg: "bg-violet-light", fg: "text-violet" },
  amber: { bg: "bg-amber-100", fg: "text-amber-600" },
};

interface FormState {
  name: string;
  email: string;
  subject: string;
  category: string;
  message: string;
}
type Errors = Partial<Record<keyof FormState, string>>;
type Status = "idle" | "submitting" | "success" | "error";

const formSectionRef = ref<HTMLElement | null>(null);
const form = reactive<FormState>({ name: "", email: "", subject: "", category: CATEGORIES[0], message: "" });
const errors = reactive<Errors>({});
const status = ref<Status>("idle");

function setField<K extends keyof FormState>(k: K, v: FormState[K]) {
  form[k] = v;
  if (errors[k]) errors[k] = undefined;
}

function validate(): Errors {
  const e: Errors = {};
  if (!form.name.trim()) e.name = "Please enter your full name.";
  else if (form.name.trim().length > 100) e.name = "Name must be under 100 characters.";
  const email = form.email.trim();
  if (!email) e.email = "Please enter your email address.";
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) e.email = "Please enter a valid email address.";
  if (!form.subject.trim()) e.subject = "Please add a subject.";
  else if (form.subject.trim().length > 140) e.subject = "Subject must be under 140 characters.";
  if (!form.category) e.category = "Please select a support category.";
  if (!form.message.trim()) e.message = "Please write a message.";
  else if (form.message.trim().length < 10) e.message = "Message should be at least 10 characters.";
  else if (form.message.trim().length > 2000) e.message = "Message must be under 2000 characters.";
  return e;
}

function scrollToForm() {
  formSectionRef.value?.scrollIntoView({ behavior: "smooth", block: "start" });
  setTimeout(() => {
    document.getElementById("contact-name")?.focus();
  }, 400);
}

async function onSubmit() {
  const e = validate();
  Object.assign(errors, e);
  Object.keys(errors).forEach((k) => {
    if (!(k in e)) delete errors[k as keyof Errors];
  });
  if (Object.values(e).some(Boolean)) return;
  status.value = "submitting";
  try {
    await platformRepository.submitContact({ ...form, kind: "contact" });
    status.value = "success";
    Object.assign(form, { name: "", email: "", subject: "", category: CATEGORIES[0], message: "" });
  } catch {
    status.value = "error";
  }
}

const inputBase =
  "w-full rounded-xl border bg-bg px-4 h-11 text-[14px] text-ink placeholder:text-ink3/70 outline-none transition-colors focus:border-blue focus:ring-2 focus:ring-blue/20";
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg">
    <TopNav />

    <main class="flex-1">
      <!-- Hero -->
      <section class="relative border-b border-border overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(60%_50%_at_50%_0%,var(--tw-gradient-stops))] from-blue-light/70 to-transparent opacity-70" />
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 py-16 md:py-20 lg:py-24 text-center">
          <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-bg-card px-3 py-1 text-[11px] font-mono uppercase tracking-[0.14em] text-ink3 mb-5">
            <MessageSquare :size="12" class="text-blue" />
            Contact & Support
          </span>
          <h1 class="text-[34px] md:text-[48px] lg:text-[56px] font-bold text-ink tracking-[-0.03em] leading-[1.08]">How can we help?</h1>
          <p class="mt-4 text-[15.5px] md:text-[17px] text-ink3 max-w-[640px] mx-auto leading-relaxed">
            Have a question about AI Star Prep, Learn Mode, Exam Mode, your account, or
            subscription? Send us a message and our support team will help you.
          </p>
        </div>
      </section>

      <!-- Support option cards -->
      <section class="py-12 md:py-16 border-b border-border bg-bg-card/50">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="grid gap-4 md:gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="o in SUPPORT_OPTIONS"
              :key="o.title"
              class="group rounded-2xl border border-border bg-bg-card p-6 transition-all hover:border-blue/40 hover:shadow-[0_12px_32px_-16px_rgba(15,23,42,0.15)]"
            >
              <div :class="cn('w-11 h-11 rounded-xl grid place-items-center mb-4', TONE[o.tone].bg)">
                <component :is="o.icon" :size="20" :class="TONE[o.tone].fg" />
              </div>
              <h3 class="text-[16px] font-semibold text-ink tracking-[-0.01em]">{{ o.title }}</h3>
              <p class="mt-1.5 text-[13.5px] text-ink3 leading-relaxed">{{ o.desc }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Two-column contact section -->
      <section ref="formSectionRef" class="py-14 md:py-20">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="grid gap-10 lg:gap-14 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] items-start">
            <!-- Left: intro -->
            <div>
              <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-bg-card px-3 py-1 text-[11px] font-mono uppercase tracking-[0.14em] text-ink3 mb-4">
                Send a message
              </span>
              <h2 class="text-[26px] md:text-[34px] font-bold text-ink tracking-[-0.025em] leading-[1.15]">Reach the AI Star Prep team</h2>
              <p class="mt-3 text-[15px] text-ink2 leading-relaxed max-w-[440px]">
                Share as much detail as you can — the subject, category, and any steps to
                reproduce an issue help us reply faster and more accurately.
              </p>

              <ul class="mt-8 space-y-4">
                <li v-for="tip in TIPS" :key="tip.title" class="flex gap-3">
                  <span class="shrink-0 w-9 h-9 rounded-lg bg-blue-light grid place-items-center">
                    <component :is="tip.icon" :size="16" class="text-blue" />
                  </span>
                  <div>
                    <div class="text-[14px] font-semibold text-ink">{{ tip.title }}</div>
                    <div class="text-[13px] text-ink3 leading-relaxed">{{ tip.desc }}</div>
                  </div>
                </li>
              </ul>
            </div>

            <!-- Right: form -->
            <div class="rounded-2xl border border-border bg-bg-card p-6 md:p-8 shadow-[0_20px_48px_-28px_rgba(15,23,42,0.18)]">
              <div v-if="status === 'success'" class="text-center py-8">
                <div class="w-14 h-14 rounded-full bg-emerald-100 grid place-items-center mx-auto mb-4">
                  <CheckCircle2 :size="26" class="text-emerald-600" />
                </div>
                <h3 class="text-[20px] font-bold text-ink tracking-[-0.02em]">Message sent</h3>
                <p class="mt-2 text-[14px] text-ink2 max-w-[380px] mx-auto leading-relaxed">
                  Thanks for reaching out. Our support team will review your message and get
                  back to you as soon as possible.
                </p>
                <button
                  type="button"
                  class="mt-6 inline-flex items-center justify-center text-[13px] font-semibold text-white bg-blue px-5 h-10 rounded-full hover:bg-blue/90 transition-colors"
                  @click="status = 'idle'"
                >
                  Send another message
                </button>
              </div>
              <form v-else novalidate class="space-y-4" @submit.prevent="onSubmit">
                <div class="grid gap-4 sm:grid-cols-2">
                  <Field id="contact-name" label="Full name" :error="errors.name">
                    <input
                      id="contact-name"
                      type="text"
                      autocomplete="name"
                      :value="form.name"
                      maxlength="100"
                      :class="cn(inputBase, errors.name ? 'border-rose-500/60' : 'border-border')"
                      placeholder="Jane Doe"
                      @input="setField('name', ($event.target as HTMLInputElement).value)"
                    />
                  </Field>
                  <Field id="contact-email" label="Email address" :error="errors.email">
                    <input
                      id="contact-email"
                      type="email"
                      autocomplete="email"
                      :value="form.email"
                      maxlength="255"
                      :class="cn(inputBase, errors.email ? 'border-rose-500/60' : 'border-border')"
                      placeholder="you@school.com"
                      @input="setField('email', ($event.target as HTMLInputElement).value)"
                    />
                  </Field>
                </div>

                <Field id="contact-subject" label="Subject" :error="errors.subject">
                  <input
                    id="contact-subject"
                    type="text"
                    :value="form.subject"
                    maxlength="140"
                    :class="cn(inputBase, errors.subject ? 'border-rose-500/60' : 'border-border')"
                    placeholder="Briefly describe your request"
                    @input="setField('subject', ($event.target as HTMLInputElement).value)"
                  />
                </Field>

                <Field id="contact-category" label="Support category" :error="errors.category">
                  <select
                    id="contact-category"
                    :value="form.category"
                    :class="
                      cn(
                        inputBase,
                        `appearance-none pr-10 bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%228%22 viewBox=%220 0 12 8%22><path fill=%22none%22 stroke=%22%2364748b%22 stroke-width=%221.6%22 d=%22M1 1l5 5 5-5%22/></svg>')] bg-no-repeat bg-[right_1rem_center]`,
                        errors.category ? 'border-rose-500/60' : 'border-border',
                      )
                    "
                    @change="setField('category', ($event.target as HTMLSelectElement).value)"
                  >
                    <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
                  </select>
                </Field>

                <Field id="contact-message" label="Message" :error="errors.message">
                  <textarea
                    id="contact-message"
                    rows="6"
                    :value="form.message"
                    maxlength="2000"
                    :class="
                      cn(
                        'w-full rounded-xl border bg-bg px-4 py-3 text-[14px] text-ink placeholder:text-ink3/70 outline-none transition-colors focus:border-blue focus:ring-2 focus:ring-blue/20 resize-y min-h-[140px]',
                        errors.message ? 'border-rose-500/60' : 'border-border',
                      )
                    "
                    placeholder="Tell us what's happening…"
                    @input="setField('message', ($event.target as HTMLTextAreaElement).value)"
                  />
                </Field>

                <div v-if="status === 'error'" class="flex items-start gap-2 rounded-xl border border-rose-500/30 bg-rose-500/5 p-3 text-[13px] text-rose-600">
                  <AlertCircle :size="16" class="mt-0.5 shrink-0" />
                  <span>Something went wrong sending your message. Please try again.</span>
                </div>

                <div class="flex items-center justify-between gap-3 pt-1">
                  <p class="text-[12px] text-ink3">We reply to every message we receive.</p>
                  <button
                    type="submit"
                    :disabled="status === 'submitting'"
                    class="inline-flex items-center justify-center gap-2 text-[14px] font-semibold text-white bg-blue px-6 h-11 rounded-full hover:bg-blue/90 hover:shadow-[0_8px_24px_-6px_rgba(59,110,248,0.35)] transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                  >
                    <template v-if="status === 'submitting'">
                      <Loader2 :size="16" class="animate-spin" />
                      Sending…
                    </template>
                    <template v-else>
                      <Send :size="15" />
                      Send Message
                    </template>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>

      <!-- Helpful resources -->
      <section class="py-14 md:py-20 border-t border-border bg-bg-card/50">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="text-center mb-10">
            <h2 class="text-[26px] md:text-[32px] font-bold text-ink tracking-[-0.025em]">You may find your answer faster</h2>
            <p class="mt-2 text-[15px] text-ink3">Browse the resources students most often need.</p>
          </div>
          <div class="grid gap-4 md:gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <RouterLink
              v-for="r in RESOURCES"
              :key="r.title"
              :to="r.to"
              class="group rounded-2xl border border-border bg-bg-card p-6 transition-all hover:border-blue/40 hover:shadow-[0_12px_32px_-16px_rgba(15,23,42,0.15)]"
            >
              <div :class="cn('w-11 h-11 rounded-xl grid place-items-center mb-4', TONE[r.tone].bg)">
                <component :is="r.icon" :size="20" :class="TONE[r.tone].fg" />
              </div>
              <h3 class="text-[16px] font-semibold text-ink tracking-[-0.01em]">{{ r.title }}</h3>
              <p class="mt-1.5 text-[13.5px] text-ink3 leading-relaxed">{{ r.desc }}</p>
              <span class="mt-4 inline-flex items-center gap-1 text-[13px] font-semibold text-blue">
                Open
                <ArrowRight :size="14" class="transition-transform group-hover:translate-x-0.5" />
              </span>
            </RouterLink>
          </div>
        </div>
      </section>

      <!-- Final CTA -->
      <section class="py-14 md:py-20 border-t border-border">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div class="max-w-[720px] mx-auto text-center bg-bg-card border border-border rounded-2xl p-8 md:p-12">
            <div class="w-12 h-12 rounded-xl bg-violet-light grid place-items-center mx-auto mb-5">
              <MessageSquare :size="22" class="text-violet" />
            </div>
            <h2 class="text-[24px] md:text-[30px] font-bold text-ink tracking-[-0.025em]">Still need help?</h2>
            <p class="mt-3 text-[15px] text-ink2 max-w-[480px] mx-auto leading-relaxed">
              Send us a message and the AI Star Prep support team will guide you.
            </p>
            <div class="mt-7">
              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 text-[14px] font-semibold text-white bg-blue px-6 h-12 rounded-full hover:bg-blue/90 hover:shadow-[0_8px_24px_-6px_rgba(59,110,248,0.35)] transition-all"
                @click="scrollToForm"
              >
                Contact Support
                <ArrowRight :size="15" />
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
