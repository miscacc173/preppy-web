<script setup lang="ts">
import { AccordionContent as RekaContent, type AccordionContentProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<AccordionContentProps & { class?: string }>();
</script>
<template>
  <RekaContent v-bind="props" class="overflow-hidden text-sm data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down">
    <div :class="cn('pb-4 pt-0', props.class)"><slot /></div>
  </RekaContent>
</template>
