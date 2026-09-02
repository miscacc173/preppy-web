<script setup lang="ts">
import { DrawerPortal, DrawerContent as VaulContent } from "vaul-vue";
import { cn } from "@/lib/utils";
import DrawerOverlay from "./DrawerOverlay.vue";

const props = defineProps<{ class?: string }>();
</script>
<template>
  <DrawerPortal>
    <DrawerOverlay />
    <VaulContent
      v-bind="props"
      :class="cn('fixed inset-x-0 bottom-0 z-50 mt-24 flex h-auto flex-col rounded-t-[10px] border bg-background', props.class)"
    >
      <div class="mx-auto mt-4 h-2 w-[100px] rounded-full bg-muted" />
      <slot />
    </VaulContent>
  </DrawerPortal>
</template>
