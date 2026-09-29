<script setup lang="ts">
import { DialogDescription as RekaDialogDescription, type DialogDescriptionProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<DialogDescriptionProps & { class?: string }>();
</script>
<template>
  <RekaDialogDescription v-bind="props" :class="cn('text-sm text-muted-foreground', props.class)"><slot /></RekaDialogDescription>
</template>
