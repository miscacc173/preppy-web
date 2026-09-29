<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { BrainCircuit, LoaderCircle, Search } from "lucide-vue-next";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import TutorQuestionFeedback from "@/components/app/TutorQuestionFeedback.vue";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";
import type { UiTutorReviewItem } from "@/data/models/learningSession";

const items = ref<UiTutorReviewItem[]>([]);
const loading = ref(true);
const error = ref("");
const query = ref("");
const filtered = computed(() => {
  const needle = query.value.trim().toLowerCase();
  return needle ? items.value.filter((item) => `${item.subject} ${item.topic} ${item.questionText}`.toLowerCase().includes(needle)) : items.value;
});

onMounted(async () => {
  try { items.value = await learningSessionRepository.tutorReviewQueue(); }
  catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load the review queue."; }
  finally { loading.value = false; }
});
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <TopNav />
    <main class="mx-auto w-full max-w-[1050px] flex-1 px-5 py-9">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="label-mono text-violet">Tutor quality review</p>
          <h1 class="mt-2 text-[30px] font-bold text-ink">AI question, answer and scoring review</h1>
          <p class="mt-1 max-w-2xl text-[13px] text-ink3">Review only content marked as AI-generated. Student identities and full result dashboards are not exposed here.</p>
        </div>
        <label class="flex min-w-[260px] items-center gap-2 rounded-xl border border-border bg-bg-card px-3 py-2.5">
          <Search :size="15" class="text-ink4" /><input v-model="query" class="w-full bg-transparent text-[13px] outline-none" placeholder="Search subject or question" />
        </label>
      </div>

      <div v-if="loading" class="mt-8 grid place-items-center rounded-2xl border border-border bg-bg-card py-20"><LoaderCircle :size="24" class="animate-spin text-violet" /></div>
      <p v-else-if="error" class="mt-6 rounded-xl bg-pink-light p-4 text-[13px] text-pink">{{ error }}</p>
      <div v-else-if="!filtered.length" class="mt-8 rounded-2xl border border-dashed border-border bg-bg-card p-10 text-center">
        <BrainCircuit :size="28" class="mx-auto text-ink4" /><p class="mt-3 text-[15px] font-semibold text-ink">No AI review items</p><p class="mt-1 text-[12px] text-ink3">Items appear when an AI-generated question, answer or score is submitted for review.</p>
      </div>
      <div v-else class="mt-8 space-y-5">
        <article v-for="item in filtered" :key="`${item.sessionId}-${item.questionKey}`" class="rounded-2xl border border-border bg-bg-card p-5 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-[11px] font-semibold uppercase tracking-wider text-violet">{{ item.subject }} · {{ item.topic }}</p><span class="rounded-full bg-bg-soft px-2.5 py-1 font-mono text-[11px] text-ink2">AI score {{ item.marksAwarded }}/{{ item.marks }}</span></div>
          <h2 class="mt-3 text-[15px] font-semibold text-ink">{{ item.questionText }}</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2"><div class="rounded-xl bg-bg-soft p-4"><p class="label-mono">Submitted answer</p><p class="mt-2 whitespace-pre-wrap text-[12px] text-ink2">{{ item.submittedAnswer || 'No answer submitted' }}</p></div><div class="rounded-xl bg-violet-light/40 p-4"><p class="label-mono text-violet">AI answer</p><p class="mt-2 whitespace-pre-wrap text-[12px] text-ink2">{{ item.aiAnswer || 'No AI answer supplied' }}</p></div></div>
          <TutorQuestionFeedback :session-type="item.sessionType" :session-id="item.sessionId" :question-key="item.questionKey" :question-text="item.questionText" :answer-text="item.aiAnswer || item.submittedAnswer" :scoring-text="`${item.marksAwarded}/${item.marks}`" />
        </article>
      </div>
    </main>
    <Footer />
  </div>
</template>
