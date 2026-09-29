<script setup lang="ts">
import { DialogTitle as RekaDialogTitle, type DialogTitleProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<DialogTitleProps & { class?: string }>();
</script>
<template>
  <RekaDialogTitle v-bind="props" :class="cn('text-lg font-semibold leading-none tracking-tight', props.class)"><slot /></RekaDialogTitle>
</template>
