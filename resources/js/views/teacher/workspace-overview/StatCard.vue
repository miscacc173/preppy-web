<script setup lang="ts">
import { TONES } from "@/components/teacher/workspace-ui";
defineProps<{ label: string; value: number | string; icon: unknown; tone: keyof typeof TONES }>();
</script>
<template>
  <div class="bg-bg-card border border-border rounded-[14px] p-4 shadow-[0_2px_10px_rgba(15,23,42,0.03)]">
    <div class="flex items-center gap-2.5 mb-2">
      <div :class="`w-8 h-8 rounded-lg grid place-items-center ${TONES[tone].bg}`">
        <component :is="icon" :size="15" :class="TONES[tone].text" />
      </div>
      <p class="text-[11.5px] font-semibold uppercase tracking-wider text-ink3">{{ label }}</p>
    </div>
    <div class="text-[24px] font-bold text-ink tracking-[-0.02em]">{{ value }}</div>
  </div>
</template>
