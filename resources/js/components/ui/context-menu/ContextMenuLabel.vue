<script setup lang="ts">
import { ContextMenuLabel as RekaLabel, type ContextMenuLabelProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<ContextMenuLabelProps & { inset?: boolean; class?: string }>();
</script>
<template>
  <RekaLabel v-bind="props" :class="cn('px-2 py-1.5 text-sm font-semibold text-foreground', inset && 'pl-8', props.class)">
    <slot />
  </RekaLabel>
</template>
