<script setup lang="ts">
import { AlertDialogTitle as RekaTitle, type AlertDialogTitleProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<AlertDialogTitleProps & { class?: string }>();
</script>
<template><RekaTitle v-bind="props" :class="cn('text-lg font-semibold', props.class)"><slot /></RekaTitle></template>
