<script setup lang="ts">
import { DropdownMenuLabel as RekaLabel, type DropdownMenuLabelProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<DropdownMenuLabelProps & { inset?: boolean; class?: string }>();
</script>
<template>
  <RekaLabel v-bind="props" :class="cn('px-2 py-1.5 text-sm font-semibold', inset && 'pl-8', props.class)">
    <slot />
  </RekaLabel>
</template>
