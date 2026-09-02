<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import { LoaderCircle, MessageSquareText, Save, Trash2 } from "lucide-vue-next";
import { currentUser } from "@/lib/api";
import { tutorFeedbackRepository } from "@/data/repositories/tutorFeedbackRepository";

type FeedbackKind = "question" | "answer";
interface FeedbackState { id: number; feedback: string; verdict: string; saving: boolean; saved: boolean; error: string }

const props = defineProps<{ sessionType: "learn" | "exam"; sessionId: string; questionKey: string; questionText: string; answerText: string; scoringText?: string }>();
const isTutor = computed(() => currentUser()?.roles?.some((role) => role.slug === "tutor") ?? false);
const loading = reactive({ value: false });
const states = reactive<Record<FeedbackKind, FeedbackState>>({
  question: { id: 0, feedback: "", verdict: "", saving: false, saved: false, error: "" },
  answer: { id: 0, feedback: "", verdict: "", saving: false, saved: false, error: "" },
});
const feedbackKinds: FeedbackKind[] = ["question", "answer"];

const feedbackKey = (kind: FeedbackKind) => `${props.questionKey}:${kind}`;
function reset(kind: FeedbackKind) { Object.assign(states[kind], { id: 0, feedback: "", verdict: "", saving: false, saved: false, error: "" }); }

async function load() {
  reset("question"); reset("answer");
  if (!isTutor.value || !props.sessionId) return;
  loading.value = true;
  try {
    const existing = await tutorFeedbackRepository.list(props.sessionType, props.sessionId);
    (["question", "answer"] as FeedbackKind[]).forEach((kind) => {
      const match = existing.find((item) => item.questionKey === feedbackKey(kind))
        ?? (kind === "question" ? existing.find((item) => item.questionKey === props.questionKey) : undefined);
      if (match) Object.assign(states[kind], { id: match.id, feedback: match.feedback, verdict: match.verdict });
    });
  } catch (reason) {
    states.question.error = reason instanceof Error ? reason.message : "Unable to load tutor feedback.";
  } finally { loading.value = false; }
}

async function save(kind: FeedbackKind) {
  const state = states[kind];
  if (!state.feedback.trim()) return;
  state.saving = true; state.saved = false; state.error = "";
  try {
    const result = await tutorFeedbackRepository.save({
      session_type: props.sessionType,
      session_id: props.sessionId,
      question_key: feedbackKey(kind),
      question_text: props.questionText,
      answer_text: kind === "answer" ? props.answerText || null : null,
      feedback: state.feedback.trim(),
      verdict: state.verdict || null,
      metadata: { feedback_type: kind, ...(props.scoringText ? { scoring_snapshot: props.scoringText } : {}) },
    });
    state.id = result.id; state.saved = true;
  } catch (reason) {
    state.error = reason instanceof Error ? reason.message : `Unable to save ${kind} feedback.`;
  } finally { state.saving = false; }
}

async function remove(kind: FeedbackKind) {
  const state = states[kind];
  if (!state.id) return;
  state.saving = true;
  try { await tutorFeedbackRepository.remove(state.id); reset(kind); }
  catch (reason) { state.error = reason instanceof Error ? reason.message : `Unable to delete ${kind} feedback.`; state.saving = false; }
}

watch(() => [props.sessionType, props.sessionId, props.questionKey], load, { immediate: true });
</script>

<template>
  <section v-if="isTutor && sessionId" class="mt-4 rounded-xl border border-violet/25 bg-violet-light/30 p-4">
    <div class="flex items-center gap-2">
      <MessageSquareText :size="15" class="text-violet" />
      <p class="text-[11px] font-semibold uppercase tracking-wider text-violet">Tutor content feedback</p>
    </div>
    <div v-if="loading.value" class="mt-3 grid gap-3 sm:grid-cols-2"><div class="h-36 animate-pulse rounded-lg bg-bg-soft" /><div class="h-36 animate-pulse rounded-lg bg-bg-soft" /></div>
    <div v-else class="mt-3 grid gap-3 lg:grid-cols-2">
      <article v-for="kind in feedbackKinds" :key="kind" class="rounded-xl border border-border bg-bg-card p-3">
        <div class="flex items-center justify-between gap-2">
          <p class="text-[12px] font-bold capitalize text-ink">{{ kind }} feedback</p>
          <span v-if="states[kind].saved" class="text-[10px] font-semibold text-mint">Saved</span>
        </div>
        <p class="mt-1 line-clamp-2 text-[10px] text-ink3">{{ kind === 'question' ? questionText : (answerText || 'No answer available yet.') }}</p>
        <select v-model="states[kind].verdict" class="ops-input mt-3">
          <option value="">No verdict</option><option value="correct">Correct</option><option value="partially_correct">Partially correct</option><option value="incorrect">Incorrect</option><option value="needs_review">Needs review</option>
        </select>
        <textarea v-model="states[kind].feedback" rows="3" class="ops-input mt-2 resize-y" :placeholder="`Add feedback against this ${kind}…`" />
        <p v-if="states[kind].error" class="mt-2 text-[11px] text-pink">{{ states[kind].error }}</p>
        <div class="mt-3 flex justify-end gap-2">
          <button v-if="states[kind].id" type="button" class="ops-button-secondary inline-flex items-center gap-1.5 hover:text-pink" :disabled="states[kind].saving" @click="remove(kind)"><Trash2 :size="13" />Delete</button>
          <button type="button" class="ops-button-primary inline-flex min-w-28 items-center justify-center gap-2" :disabled="states[kind].saving || !states[kind].feedback.trim()" @click="save(kind)"><LoaderCircle v-if="states[kind].saving" :size="13" class="animate-spin" /><Save v-else :size="13" />{{ states[kind].id ? 'Update' : 'Save' }}</button>
        </div>
      </article>
    </div>
  </section>
</template>
