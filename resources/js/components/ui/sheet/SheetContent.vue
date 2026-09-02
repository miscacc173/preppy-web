<script setup lang="ts">
import { DialogPortal, DialogContent, DialogClose, type DialogContentProps } from "reka-ui";
import { X } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import SheetOverlay from "./SheetOverlay.vue";
import { sheetVariants, type SheetVariants } from ".";

const props = withDefaults(
  defineProps<DialogContentProps & { side?: SheetVariants["side"]; class?: string }>(),
  { side: "right" },
);
</script>
<template>
  <DialogPortal>
    <SheetOverlay />
    <DialogContent v-bind="props" :class="cn(sheetVariants({ side }), props.class)">
      <DialogClose class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background cursor-pointer transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-secondary">
        <X class="h-4 w-4" />
        <span class="sr-only">Close</span>
      </DialogClose>
      <slot />
    </DialogContent>
  </DialogPortal>
</template>
