<script setup lang="ts">
import { provide } from "vue";
import { ToggleGroupRoot, type ToggleGroupRootProps } from "reka-ui";
import { cn } from "@/lib/utils";
import type { ToggleVariants } from "@/components/ui/toggle";
import { TOGGLE_GROUP_CONTEXT_KEY } from "./context";

const props = defineProps<
  ToggleGroupRootProps & { variant?: ToggleVariants["variant"]; size?: ToggleVariants["size"]; class?: string }
>();

provide(TOGGLE_GROUP_CONTEXT_KEY, { variant: props.variant, size: props.size });
</script>

<template>
  <ToggleGroupRoot v-bind="props" :class="cn('flex items-center justify-center gap-1', props.class)">
    <slot />
  </ToggleGroupRoot>
</template>
