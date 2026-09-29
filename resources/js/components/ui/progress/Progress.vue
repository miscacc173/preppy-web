<script setup lang="ts">
import { ProgressRoot, ProgressIndicator, type ProgressRootProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<ProgressRootProps & { class?: string }>();
</script>

<template>
  <ProgressRoot
    v-bind="props"
    :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-primary/20', props.class)"
  >
    <ProgressIndicator
      class="h-full w-full flex-1 bg-primary transition-all"
      :style="`transform: translateX(-${100 - (props.modelValue ?? 0)}%)`"
    />
  </ProgressRoot>
</template>
