<script setup lang="ts">
import { AlertDialogPortal, AlertDialogContent as RekaContent, type AlertDialogContentProps } from "reka-ui";
import { cn } from "@/lib/utils";
import AlertDialogOverlay from "./AlertDialogOverlay.vue";

const props = defineProps<AlertDialogContentProps & { class?: string }>();
</script>
<template>
  <AlertDialogPortal>
    <AlertDialogOverlay />
    <RekaContent
      v-bind="props"
      :class="cn('fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 sm:rounded-lg', props.class)"
    >
      <slot />
    </RekaContent>
  </AlertDialogPortal>
</template>
