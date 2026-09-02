<script setup lang="ts">
import { NavigationMenuIndicator as RekaIndicator, type NavigationMenuIndicatorProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<NavigationMenuIndicatorProps & { class?: string }>();
</script>
<template>
  <RekaIndicator
    v-bind="props"
    :class="cn('top-full z-[1] flex h-1.5 items-end justify-center overflow-hidden data-[state=visible]:animate-in data-[state=hidden]:animate-out data-[state=hidden]:fade-out data-[state=visible]:fade-in', props.class)"
  >
    <div class="relative top-[60%] h-2 w-2 rotate-45 rounded-tl-sm bg-border shadow-md" />
  </RekaIndicator>
</template>
