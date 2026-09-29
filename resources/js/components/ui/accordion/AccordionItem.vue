<script setup lang="ts">
import { AccordionItem as RekaItem, type AccordionItemProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<AccordionItemProps & { class?: string }>();
</script>
<template>
  <RekaItem v-bind="props" :class="cn('border-b', props.class)"><slot /></RekaItem>
</template>
