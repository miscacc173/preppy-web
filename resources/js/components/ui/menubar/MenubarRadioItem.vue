<script setup lang="ts">
import { MenubarRadioItem as RekaRadioItem, MenubarItemIndicator, type MenubarRadioItemProps } from "reka-ui";
import { Circle } from "lucide-vue-next";
import { cn } from "@/lib/utils";
const props = defineProps<MenubarRadioItemProps & { class?: string }>();
</script>
<template>
  <RekaRadioItem v-bind="props" :class="cn('relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50', props.class)">
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <MenubarItemIndicator><Circle class="h-4 w-4 fill-current" /></MenubarItemIndicator>
    </span>
    <slot />
  </RekaRadioItem>
</template>
