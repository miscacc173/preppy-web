<script setup lang="ts">
import { DropdownMenuItem as RekaItem, type DropdownMenuItemProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<DropdownMenuItemProps & { inset?: boolean; class?: string }>();
</script>
<template>
  <RekaItem
    v-bind="props"
    :class="cn('relative flex cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0', inset && 'pl-8', props.class)"
  >
    <slot />
  </RekaItem>
</template>
