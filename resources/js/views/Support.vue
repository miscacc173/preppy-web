<script setup lang="ts">
import { reactive, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { MessageSquare, Mail, Bug, Sparkles, Upload, Check } from "lucide-vue-next";
import { currentUser } from "@/lib/api";
import { platformRepository } from "@/data/repositories/platformRepository";

setPageMeta({ title: "Contact Support — AiStar Prep", description: "Talk to our team, submit a ticket, report a bug, or request a feature." });

const CHANNELS = [
  { id: "chat", label: "Live chat", desc: "Average reply 2 min", icon: MessageSquare, color: "blue" },
  { id: "email", label: "Email support", desc: "support@aistarprep.app", icon: Mail, color: "violet" },
  { id: "bug", label: "Report a bug", desc: "Something broken?", icon: Bug, color: "pink" },
  { id: "feature", label: "Feature request", desc: "Tell us what to build", icon: Sparkles, color: "gold" },
];

const CATEGORIES = ["Account", "Billing", "Learn Mode", "Exam Mode", "Bug report", "Feature request", "Other"];

const submitted = ref(false);
const ticketRef = ref(0);
const form = reactive({ subject: "", category: "Account", description: "", file: "" });

async function submit() {
  const user = currentUser();
  const result = await platformRepository.submitContact({ name: user?.name || "User", email: user?.email || "unknown@example.com", subject: form.subject, category: form.category, message: form.description, kind: "support" });
  ticketRef.value = result.data.id;
  submitted.value = true;
}

function resetForm() {
  submitted.value = false;
  Object.assign(form, { subject: "", category: "Account", description: "", file: "" });
}

function onFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  form.file = file?.name ?? "";
}
</script>

<template>
  <AppShell title="Contact Support" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="space-y-6">
      <!-- Channels -->
      <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <button
          v-for="c in CHANNELS"
          :key="c.id"
          class="text-left bg-bg-card border border-border rounded-2xl p-5 hover:border-blue transition"
        >
          <div :class="`w-10 h-10 rounded-lg grid place-items-center mb-3 bg-${c.color}-light`">
            <component :is="c.icon" :size="18" :class="`text-${c.color}`" />
          </div>
          <p class="text-[15px] font-semibold text-ink">{{ c.label }}</p>
          <p class="text-[12.5px] text-ink3 mt-0.5">{{ c.desc }}</p>
        </button>
      </section>

      <!-- Ticket form -->
      <section class="bg-bg-card border border-border rounded-2xl p-6 lg:p-7">
        <h3 class="text-[18px] font-semibold text-ink">Submit a ticket</h3>
        <p class="text-[13.5px] text-ink3 mt-1">We'll reply by email within 4 hours.</p>

        <div v-if="submitted" class="mt-6 bg-mint-light border border-mint-light rounded-xl p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-mint grid place-items-center mx-auto mb-3">
            <Check :size="22" class="text-white" />
          </div>
          <p class="text-[16px] font-semibold text-ink">Ticket received</p>
          <p class="text-[13px] text-ink2 mt-1">Reference #AS-{{ ticketRef }}. Check your email for updates.</p>
          <button class="mt-4 text-[13px] text-blue font-medium" @click="resetForm">Submit another</button>
        </div>
        <form v-else class="mt-5 space-y-4 max-w-[640px]" @submit.prevent="submit">
          <div>
            <label for="subject" class="block text-[13px] font-medium text-ink2 mb-1.5">Subject</label>
            <input
              id="subject"
              v-model="form.subject"
              required
              placeholder="Brief summary…"
              class="w-full bg-bg-soft border border-border rounded-md px-4 text-[14px] text-ink outline-none focus:border-blue"
              style="height: 52px"
            />
          </div>
          <div>
            <label for="category" class="block text-[13px] font-medium text-ink2 mb-1.5">Category</label>
            <select
              id="category"
              v-model="form.category"
              class="w-full bg-bg-soft border border-border rounded-md px-4 text-[14px] text-ink outline-none focus:border-blue"
              style="height: 52px"
            >
              <option v-for="c in CATEGORIES" :key="c">{{ c }}</option>
            </select>
          </div>
          <div>
            <label for="desc" class="block text-[13px] font-medium text-ink2 mb-1.5">Description</label>
            <textarea
              id="desc"
              v-model="form.description"
              required
              rows="6"
              placeholder="What happened? Include steps to reproduce."
              class="w-full bg-bg-soft border border-border rounded-md px-4 py-3 text-[14px] text-ink outline-none focus:border-blue resize-y min-h-[140px]"
            />
          </div>
          <div>
            <label class="block text-[13px] font-medium text-ink2 mb-1.5">Screenshot (optional)</label>
            <label class="flex items-center gap-3 bg-bg-soft border border-dashed border-border rounded-md p-4 cursor-pointer hover:bg-bg-soft2 min-h-[56px]">
              <Upload :size="16" class="text-ink3" />
              <span class="text-[13px] text-ink3">{{ form.file || "Drop an image or click to upload" }}</span>
              <input type="file" accept="image/*" class="hidden" @change="onFile" />
            </label>
          </div>
          <button
            type="submit"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-blue text-white text-[15px] font-semibold px-6 h-12 rounded-lg hover:opacity-90"
          >
            Submit ticket
          </button>
        </form>
      </section>
    </div>
  </AppShell>
</template>
