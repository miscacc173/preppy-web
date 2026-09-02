<script setup lang="ts">
import { AccordionHeader, AccordionTrigger as RekaTrigger, type AccordionTriggerProps } from "reka-ui";
import { ChevronDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";
const props = defineProps<AccordionTriggerProps & { class?: string }>();
</script>
<template>
  <AccordionHeader class="flex">
    <RekaTrigger
      v-bind="props"
      :class="cn('flex flex-1 items-center justify-between py-4 text-sm font-medium cursor-pointer transition-all hover:underline text-left [&[data-state=open]>svg]:rotate-180', props.class)"
    >
      <slot />
      <ChevronDown class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" />
    </RekaTrigger>
  </AccordionHeader>
</template>
