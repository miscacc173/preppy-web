<script setup lang="ts">
import { Plus } from "lucide-vue-next";

interface PrimaryAction {
  label: string;
  icon?: unknown;
  onClick?: () => void;
}

const props = defineProps<{ title: string; subtitle: string; primaryAction?: PrimaryAction }>();
</script>

<template>
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-[22px] md:text-[26px] font-bold text-ink tracking-[-0.02em] leading-tight">{{ title }}</h1>
      <p class="text-[13.5px] text-ink2 font-light mt-1.5 max-w-[640px] leading-[1.6]">{{ subtitle }}</p>
    </div>
    <button
      v-if="primaryAction"
      class="inline-flex items-center gap-2 bg-violet-600 text-white text-[13.5px] font-semibold px-4 py-2.5 rounded-full hover:bg-violet-700 hover:shadow-[0_8px_24px_-6px_rgba(124,58,237,0.35)] transition-all self-start md:self-auto"
      @click="primaryAction.onClick?.()"
    >
      <component :is="primaryAction.icon ?? Plus" :size="15" /> {{ primaryAction.label }}
    </button>
  </div>
</template>
