<script setup lang="ts">
import { Award, CheckCircle2 } from "lucide-vue-next";
withDefaults(defineProps<{ title: string; desc: string; unlocked?: boolean }>(), { unlocked: false });
</script>
<template>
  <div :class="`bg-bg-card border border-border rounded-[14px] p-4 flex items-start gap-3 ${unlocked ? '' : 'opacity-70'}`">
    <div :class="`w-10 h-10 rounded-xl grid place-items-center shrink-0 ${unlocked ? 'bg-violet-50' : 'bg-bg-soft'}`">
      <CheckCircle2 v-if="unlocked" :size="18" class="text-violet-600" />
      <Award v-else :size="18" class="text-ink3" />
    </div>
    <div class="min-w-0">
      <div class="text-[13.5px] font-semibold text-ink leading-tight">{{ title }}</div>
      <div class="text-[12px] text-ink3 font-light mt-0.5 leading-[1.5]">{{ desc }}</div>
    </div>
  </div>
</template>
