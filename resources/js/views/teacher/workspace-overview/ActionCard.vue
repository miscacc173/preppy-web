<script setup lang="ts">
import { ArrowRight } from "lucide-vue-next";
import { TONES } from "@/components/teacher/workspace-ui";
defineProps<{ icon: unknown; title: string; desc: string; tone: keyof typeof TONES; to: string }>();
</script>
<template>
  <RouterLink
    :to="to"
    class="group text-left bg-bg-card border border-border rounded-[14px] p-4 flex items-start gap-3 hover:border-violet-300 hover:-translate-y-[1px] hover:shadow-[0_10px_30px_rgba(15,23,42,0.06)] transition-all"
  >
    <div :class="`w-10 h-10 rounded-xl grid place-items-center shrink-0 ${TONES[tone].bg}`">
      <component :is="icon" :size="18" :class="TONES[tone].text" />
    </div>
    <div class="min-w-0 flex-1">
      <div class="text-[14px] font-semibold text-ink leading-tight">{{ title }}</div>
      <div class="text-[12.5px] text-ink3 font-light mt-0.5">{{ desc }}</div>
    </div>
    <ArrowRight :size="15" class="text-ink3 group-hover:translate-x-0.5 group-hover:text-violet-600 transition-all mt-1" />
  </RouterLink>
</template>
