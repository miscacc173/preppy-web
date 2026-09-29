<script setup lang="ts">
import { Toggle as RekaToggle, type ToggleProps } from "reka-ui";
import { cn } from "@/lib/utils";
import { toggleVariants, type ToggleVariants } from ".";

const props = defineProps<
  ToggleProps & { variant?: ToggleVariants["variant"]; size?: ToggleVariants["size"]; class?: string }
>();
</script>

<template>
  <RekaToggle v-bind="props" :class="cn(toggleVariants({ variant, size }), props.class)">
    <slot />
  </RekaToggle>
</template>
