<script setup lang="ts">
import { NavigationMenuViewport as RekaViewport, type NavigationMenuViewportProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<NavigationMenuViewportProps & { class?: string }>();
</script>
<template>
  <div class="absolute left-0 top-full flex justify-center">
    <RekaViewport
      v-bind="props"
      :class="cn('origin-top-center relative mt-1.5 h-[var(--reka-navigation-menu-viewport-height)] w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-90 md:w-[var(--reka-navigation-menu-viewport-width)]', props.class)"
    />
  </div>
</template>
