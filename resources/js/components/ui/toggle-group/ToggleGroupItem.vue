<script setup lang="ts">
import { inject } from "vue";
import { ToggleGroupItem as RekaToggleGroupItem, type ToggleGroupItemProps } from "reka-ui";
import { cn } from "@/lib/utils";
import { toggleVariants, type ToggleVariants } from "@/components/ui/toggle";
import { TOGGLE_GROUP_CONTEXT_KEY } from "./context";

const props = defineProps<
  ToggleGroupItemProps & { variant?: ToggleVariants["variant"]; size?: ToggleVariants["size"]; class?: string }
>();

const context = inject(TOGGLE_GROUP_CONTEXT_KEY, { variant: undefined, size: undefined });
</script>

<template>
  <RekaToggleGroupItem
    v-bind="props"
    :class="cn(toggleVariants({ variant: context.variant ?? variant, size: context.size ?? size }), props.class)"
  >
    <slot />
  </RekaToggleGroupItem>
</template>
