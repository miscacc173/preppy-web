<script setup lang="ts">
import { ref } from "vue";
import { Maximize2, X } from "lucide-vue-next";
import type { QuestionVisualSpec } from ".";
import VisualSVG from "./VisualSVG.vue";

defineProps<{ visual: QuestionVisualSpec; figureNumber?: number }>();

const open = ref(false);
</script>

<template>
  <figure class="mb-5 bg-bg-soft border border-border rounded-xl p-4 sm:p-5">
    <div class="relative">
      <button
        type="button"
        aria-label="Enlarge diagram"
        class="absolute top-1 right-1 z-10 inline-flex items-center justify-center w-9 h-9 rounded-md bg-bg-card/90 border border-border text-ink3 hover:text-ink hover:border-ink3 backdrop-blur"
        @click="open = true"
      >
        <Maximize2 :size="14" />
      </button>
      <div
        class="w-full grid place-items-center cursor-zoom-in"
        role="img"
        :aria-label="visual.caption ?? 'Question diagram'"
        @click="open = true"
      >
        <VisualSVG :spec="visual" class="w-full max-w-[480px] h-auto" />
      </div>
    </div>
    <figcaption class="mt-3 text-center text-[11.5px] text-ink3 font-mono">
      Figure {{ figureNumber ?? 1 }}{{ visual.caption ? ` — ${visual.caption}` : "" }}
    </figcaption>
  </figure>

  <div
    v-if="open"
    class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm grid place-items-center p-4"
    role="dialog"
    aria-modal="true"
    @click="open = false"
  >
    <div class="relative bg-bg-card border border-border rounded-2xl p-6 max-w-[92vw] max-h-[92vh] overflow-auto" @click.stop>
      <button
        type="button"
        aria-label="Close"
        class="absolute top-3 right-3 inline-flex items-center justify-center w-10 h-10 rounded-md bg-bg-soft border border-border text-ink2 hover:text-ink"
        @click="open = false"
      >
        <X :size="16" />
      </button>
      <VisualSVG :spec="visual" class="w-[min(80vw,800px)] h-auto" />
      <p v-if="visual.caption" class="mt-4 text-center text-[13px] text-ink2">{{ visual.caption }}</p>
    </div>
  </div>
</template>
