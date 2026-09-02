<script setup lang="ts">
import { ref } from "vue";
import { X, Save, Send, Trash2 } from "lucide-vue-next";

defineProps<{ answered: number; total: number }>();
const emit = defineEmits<{ close: []; "resume-later": []; submit: []; discard: [] }>();

const confirmDiscard = ref(false);
</script>

<template>
  <div class="fixed inset-0 z-50 grid place-items-center p-4 bg-ink/40 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="w-full max-w-[520px] bg-bg-card border border-border rounded-2xl shadow-lift p-6 relative">
      <button
        aria-label="Close"
        class="absolute top-3 right-3 w-9 h-9 grid place-items-center text-ink3 hover:text-ink rounded-md hover:bg-bg-soft"
        @click="emit('close')"
      >
        <X :size="16" />
      </button>
      <p class="label-mono mb-1">Exit session</p>
      <h3 class="text-[20px] font-bold text-ink tracking-[-0.02em]">You've answered {{ answered }} of {{ total }}</h3>
      <p class="text-[13px] text-ink3 mt-1 mb-5">Choose how you'd like to leave. Your progress is safe until you discard.</p>

      <div v-if="confirmDiscard" class="space-y-3">
        <div class="bg-pink-light/50 border border-pink-light rounded-lg p-4">
          <p class="text-[13px] text-ink font-semibold mb-1">Discard this session?</p>
          <p class="text-[12px] text-ink3">
            All {{ answered }} answered {{ answered === 1 ? "question is" : "questions are" }} permanently deleted.
            This cannot be undone.
          </p>
        </div>
        <div class="flex gap-2">
          <button
            class="flex-1 inline-flex items-center justify-center gap-2 bg-bg-soft border border-border text-ink2 text-[13px] font-semibold h-11 rounded-lg hover:bg-bg-soft2"
            @click="confirmDiscard = false"
          >
            Cancel
          </button>
          <button
            class="flex-1 inline-flex items-center justify-center gap-2 bg-pink text-white text-[13px] font-semibold h-11 rounded-lg hover:opacity-90"
            @click="emit('discard')"
          >
            <Trash2 :size="14" /> Yes, discard
          </button>
        </div>
      </div>

      <div v-else class="space-y-2.5">
        <button
          class="w-full text-left p-4 rounded-xl border-2 border-violet bg-violet-light/50 hover:bg-violet-light transition flex items-start gap-3"
          @click="emit('resume-later')"
        >
          <div class="w-9 h-9 rounded-lg bg-violet grid place-items-center shrink-0">
            <Save :size="16" class="text-white" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[14px] font-semibold text-ink">Resume later</p>
            <p class="text-[12px] text-ink3 mt-0.5">
              Save exactly where you are. Continue from question {{ Math.min(total, answered + 1) }} next time.
            </p>
          </div>
        </button>

        <button
          :disabled="answered === 0"
          class="w-full text-left p-4 rounded-xl border-2 border-border bg-bg-card hover:border-blue disabled:opacity-40 disabled:cursor-not-allowed transition flex items-start gap-3"
          @click="emit('submit')"
        >
          <div class="w-9 h-9 rounded-lg bg-blue-light grid place-items-center shrink-0">
            <Send :size="16" class="text-blue" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[14px] font-semibold text-ink">Submit progress</p>
            <p class="text-[12px] text-ink3 mt-0.5">Get AI feedback on the {{ answered }} question{{ answered === 1 ? "" : "s" }} you've answered so far.</p>
          </div>
        </button>

        <button
          class="w-full text-left p-4 rounded-xl border-2 border-border bg-bg-card hover:border-pink transition flex items-start gap-3"
          @click="confirmDiscard = true"
        >
          <div class="w-9 h-9 rounded-lg bg-pink-light grid place-items-center shrink-0">
            <Trash2 :size="16" class="text-pink" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[14px] font-semibold text-ink">Discard session</p>
            <p class="text-[12px] text-ink3 mt-0.5">Delete everything and start fresh next time.</p>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
