<script setup lang="ts">
import { MenubarItem as RekaItem, type MenubarItemProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<MenubarItemProps & { inset?: boolean; class?: string }>();
</script>
<template>
  <RekaItem v-bind="props" :class="cn('relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50', inset && 'pl-8', props.class)">
    <slot />
  </RekaItem>
</template>
