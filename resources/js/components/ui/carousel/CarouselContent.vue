<script setup lang="ts">
import { inject } from "vue";
import { cn } from "@/lib/utils";
import { CAROUSEL_CONTEXT_KEY } from "./context";

const props = defineProps<{ class?: string }>();
const ctx = inject(CAROUSEL_CONTEXT_KEY)!;
</script>

<template>
  <div ref="ctx.emblaRef" class="overflow-hidden">
    <div :class="cn('flex', ctx.orientation === 'horizontal' ? '-ml-4' : '-mt-4 flex-col', props.class)">
      <slot />
    </div>
  </div>
</template>
