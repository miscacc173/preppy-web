<script setup lang="ts">
import { SliderRoot, SliderTrack, SliderRange, SliderThumb, type SliderRootProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<SliderRootProps & { class?: string }>();
</script>

<template>
  <SliderRoot v-bind="props" :class="cn('relative flex w-full touch-none select-none items-center', props.class)">
    <SliderTrack class="relative h-1.5 w-full grow overflow-hidden rounded-full bg-primary/20">
      <SliderRange class="absolute h-full bg-primary" />
    </SliderTrack>
    <SliderThumb
      v-for="(_, i) in props.modelValue ?? [0]"
      :key="i"
      class="block h-4 w-4 rounded-full border border-primary/50 bg-background shadow transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
    />
  </SliderRoot>
</template>
