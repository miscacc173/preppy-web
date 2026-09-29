<script setup lang="ts">
import { Plus } from "lucide-vue-next";

const props = defineProps<{ icon: unknown; title: string; desc: string; cta?: string }>();
const emit = defineEmits<{ cta: [] }>();
</script>

<template>
  <div class="bg-bg-card border border-dashed border-border rounded-[14px] p-6 md:p-10 text-center">
    <div class="w-12 h-12 rounded-xl bg-violet-50 grid place-items-center mx-auto mb-3">
      <component :is="icon" :size="22" class="text-violet-600" />
    </div>
    <h3 class="text-[15.5px] font-bold text-ink tracking-[-0.01em]">{{ title }}</h3>
    <p class="text-[13px] text-ink3 font-light mt-1.5 max-w-[460px] mx-auto leading-[1.6]">{{ desc }}</p>
    <button
      v-if="cta"
      class="mt-5 inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13px] font-semibold px-4 py-2 rounded-full hover:bg-violet-700 transition"
      @click="emit('cta')"
    >
      <Plus :size="14" /> {{ cta }}
    </button>
  </div>
</template>
