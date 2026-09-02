<script setup lang="ts">
import { computed, ref } from "vue";
import { PlayCircle, ArrowRight, Clock, Trash2 } from "lucide-vue-next";
import { clearInProgress, relativeTime, sessionProgress, type InProgressSession } from "@/lib/learnSessionStore";
import ConfirmModal from "@/components/operations/ConfirmModal.vue";

const props = defineProps<{ s: InProgressSession; compact: boolean }>();
const confirmOpen = ref(false);

const progress = computed(() => sessionProgress(props.s));
const nextQ = computed(() => Math.min(progress.value.total, props.s.qIdx + 1));
const topicLabel = computed(() =>
  props.s.topics.length === 0 ? "All topics" : props.s.topics.length === 1 ? props.s.topics[0] : `${props.s.topics.length} topics`,
);

function discard(e?: Event) {
  e?.preventDefault();
  e?.stopPropagation();
  confirmOpen.value = true;
}
function confirmDiscard() { clearInProgress(props.s.id); confirmOpen.value = false; }
</script>

<template>
  <div
    v-if="compact"
    class="group relative flex items-center gap-4 bg-gradient-to-r from-violet-light/70 to-blue-light/40 border border-violet-light rounded-2xl p-4 hover:border-violet transition"
  >
    <RouterLink
      :to="{ path: '/learn/session', query: { resume: '1', sid: s.id } }"
      class="absolute inset-0 rounded-2xl"
      :aria-label="`Resume ${s.subject} ${s.kind} Mode`"
    />
    <div class="w-11 h-11 rounded-lg bg-violet grid place-items-center shrink-0 relative">
      <PlayCircle :size="20" class="text-white" />
    </div>
    <div class="min-w-0 flex-1 relative">
      <div class="flex items-center gap-2 mb-0.5 flex-wrap">
        <p class="text-[13px] font-semibold text-ink truncate">{{ s.subject }} · {{ s.kind }} Mode</p>
        <span class="text-[10px] font-mono uppercase tracking-wider bg-violet text-white rounded-full px-2 py-0.5">In progress</span>
      </div>
      <p class="text-[12px] text-ink3 truncate">
        {{ topicLabel }} · Question {{ nextQ }} of {{ progress.total }} · {{ progress.pct }}% · {{ relativeTime(s.updatedAt) }}
      </p>
    </div>
    <span class="text-[12px] font-semibold text-violet inline-flex items-center gap-1 shrink-0 group-hover:gap-2 transition-all relative">
      Resume <ArrowRight :size="14" />
    </span>
    <button
      type="button"
      class="relative shrink-0 grid place-items-center w-8 h-8 rounded-md text-ink4 hover:text-pink hover:bg-white/60"
      aria-label="Discard session"
      title="Discard session"
      @click="discard($event)"
    >
      <Trash2 :size="14" />
    </button>
  </div>

  <div v-else class="bg-gradient-to-br from-violet-light/70 to-blue-light/30 border border-violet-light rounded-2xl p-5">
    <div class="flex items-start gap-3 mb-4">
      <div class="w-10 h-10 rounded-lg bg-violet grid place-items-center shrink-0">
        <PlayCircle :size="18" class="text-white" />
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <p class="text-[14px] font-semibold text-ink truncate">{{ s.subject }} · {{ s.kind }} Mode</p>
          <span class="text-[10px] font-mono uppercase tracking-wider bg-violet text-white rounded-full px-2 py-0.5">In progress</span>
        </div>
        <p class="text-[11.5px] text-ink3 truncate">{{ topicLabel }}</p>
        <p class="text-[11.5px] text-ink3 inline-flex items-center gap-1.5 mt-0.5">
          <Clock :size="11" /> {{ relativeTime(s.updatedAt) }}
        </p>
      </div>
      <button
        type="button"
        class="shrink-0 grid place-items-center w-8 h-8 rounded-md text-ink4 hover:text-pink hover:bg-white/60"
        aria-label="Discard session"
        title="Discard session"
        @click="discard()"
      >
        <Trash2 :size="14" />
      </button>
    </div>

    <div class="flex items-baseline justify-between mb-1.5">
      <span class="text-[12.5px] text-ink2 font-medium">Question {{ nextQ }} of {{ progress.total }}</span>
      <span class="font-mono text-[11.5px] text-ink3">{{ progress.pct }}% complete</span>
    </div>
    <div class="h-2 bg-white/60 rounded-full overflow-hidden mb-4">
      <div class="h-full bg-violet rounded-full transition-all" :style="{ width: `${progress.pct}%` }" />
    </div>

    <RouterLink
      :to="{ path: '/learn/session', query: { resume: '1', sid: s.id } }"
      class="w-full inline-flex items-center justify-center gap-2 bg-violet text-white text-[13.5px] font-semibold h-10 rounded-lg hover:opacity-90"
    >
      Resume <ArrowRight :size="14" />
    </RouterLink>
  </div>
  <ConfirmModal :open="confirmOpen" title="Discard session?" :message="`Discard this ${s.subject} ${s.kind} session? Progress will be lost.`" confirm-label="Discard session" @close="confirmOpen = false" @confirm="confirmDiscard" />
</template>
