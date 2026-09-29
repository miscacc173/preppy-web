<script setup lang="ts">
import { useSlots } from "vue";
defineProps<{ title: string; subtitle?: string }>();
const slots = useSlots();
</script>
<template>
  <section>
    <div class="flex items-end justify-between gap-4 mb-3">
      <div>
        <h2 class="text-[17px] md:text-[19px] font-bold text-ink tracking-[-0.01em]">{{ title }}</h2>
        <p v-if="subtitle" class="text-[13px] text-ink3 font-light mt-0.5">{{ subtitle }}</p>
      </div>
      <slot name="action" />
    </div>
    <slot />
  </section>
</template>
