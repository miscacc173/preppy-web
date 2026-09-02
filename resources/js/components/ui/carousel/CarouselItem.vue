<script setup lang="ts">
import { inject } from "vue";
import { cn } from "@/lib/utils";
import { CAROUSEL_CONTEXT_KEY } from "./context";

const props = defineProps<{ class?: string }>();
const ctx = inject(CAROUSEL_CONTEXT_KEY)!;
</script>

<template>
  <div
    role="group"
    aria-roledescription="slide"
    :class="cn('min-w-0 shrink-0 grow-0 basis-full', ctx.orientation === 'horizontal' ? 'pl-4' : 'pt-4', props.class)"
  >
    <slot />
  </div>
</template>
