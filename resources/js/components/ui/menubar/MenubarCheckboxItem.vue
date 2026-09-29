<script setup lang="ts">
import { MenubarCheckboxItem as RekaCheckboxItem, MenubarItemIndicator, type MenubarCheckboxItemProps } from "reka-ui";
import { Check } from "lucide-vue-next";
import { cn } from "@/lib/utils";
const props = defineProps<MenubarCheckboxItemProps & { class?: string }>();
</script>
<template>
  <RekaCheckboxItem v-bind="props" :class="cn('relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50', props.class)">
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <MenubarItemIndicator><Check class="h-4 w-4" /></MenubarItemIndicator>
    </span>
    <slot />
  </RekaCheckboxItem>
</template>
