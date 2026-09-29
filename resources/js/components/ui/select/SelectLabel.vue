<script setup lang="ts">
import { SelectLabel as RekaLabel, type SelectLabelProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<SelectLabelProps & { class?: string }>();
</script>
<template>
  <RekaLabel v-bind="props" :class="cn('px-2 py-1.5 text-sm font-semibold', props.class)"><slot /></RekaLabel>
</template>
