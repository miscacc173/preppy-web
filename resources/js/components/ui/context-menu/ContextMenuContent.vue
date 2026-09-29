<script setup lang="ts">
import { ContextMenuPortal, ContextMenuContent as RekaContent, type ContextMenuContentProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<ContextMenuContentProps & { class?: string }>();
</script>
<template>
  <ContextMenuPortal>
    <RekaContent
      v-bind="props"
      :class="cn(
        'z-50 max-h-(--reka-context-menu-content-available-height) min-w-[8rem] overflow-y-auto overflow-x-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 origin-(--reka-context-menu-content-transform-origin)',
        props.class,
      )"
    >
      <slot />
    </RekaContent>
  </ContextMenuPortal>
</template>
