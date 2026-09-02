<script setup lang="ts">
import { computed } from "vue";
import { Quote } from "lucide-vue-next";
import { TONE, type Testimonial } from ".";

const props = defineProps<{ t: Testimonial }>();
const tone = computed(() => TONE[props.t.tone]);
</script>

<template>
  <div class="notepad p-5 transition-all duration-300 hover:-translate-y-[2px] hover:shadow-lift">
    <Quote :size="18" :class="`${tone.text} mb-3`" :stroke-width="2" />
    <p class="text-[14px] text-ink2 leading-[1.6] font-light mb-5">"{{ t.quote }}"</p>
    <div class="border-t border-border pt-4 flex items-center gap-3">
      <div :class="`w-10 h-10 rounded-full ${tone.bg} ${tone.text} grid place-items-center font-display text-[13px] font-semibold shrink-0`">
        {{ t.initials }}
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-[13.5px] font-display font-semibold text-ink tracking-[-0.01em] truncate">{{ t.name }}</p>
        <p class="text-[11.5px] text-ink3 truncate">{{ t.subject }} · {{ t.board }}</p>
      </div>
      <span :class="`text-[10.5px] font-mono font-semibold px-2 py-1 rounded-full ${tone.bg} ${tone.text} border ${tone.border} shrink-0`">
        {{ t.improvement }}
      </span>
    </div>
  </div>
</template>
