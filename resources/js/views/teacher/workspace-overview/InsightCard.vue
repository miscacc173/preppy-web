<script setup lang="ts">
import { TONES } from "@/components/teacher/workspace-ui";
defineProps<{ tone: keyof typeof TONES; icon: unknown; title: string; body: string }>();
</script>
<template>
  <div class="bg-bg-card border border-border rounded-[14px] p-4">
    <div class="flex items-center gap-2 mb-2">
      <div :class="`w-8 h-8 rounded-lg grid place-items-center ${TONES[tone].bg}`">
        <component :is="icon" :size="15" :class="TONES[tone].text" />
      </div>
      <p class="text-[13px] font-semibold text-ink">{{ title }}</p>
    </div>
    <p class="text-[12.5px] text-ink3 font-light leading-[1.5]">{{ body }}</p>
  </div>
</template>
