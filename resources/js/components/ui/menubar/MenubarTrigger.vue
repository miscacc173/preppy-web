<script setup lang="ts">
import { MenubarTrigger as RekaTrigger, type MenubarTriggerProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<MenubarTriggerProps & { class?: string }>();
</script>
<template>
  <RekaTrigger v-bind="props" :class="cn('flex cursor-default select-none items-center rounded-sm px-3 py-1 text-sm font-medium outline-none focus:bg-accent focus:text-accent-foreground data-[state=open]:bg-accent data-[state=open]:text-accent-foreground', props.class)">
    <slot />
  </RekaTrigger>
</template>
