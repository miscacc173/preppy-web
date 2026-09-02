<script setup lang="ts">
import { AlertDialogDescription as RekaDescription, type AlertDialogDescriptionProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<AlertDialogDescriptionProps & { class?: string }>();
</script>
<template><RekaDescription v-bind="props" :class="cn('text-sm text-muted-foreground', props.class)"><slot /></RekaDescription></template>
