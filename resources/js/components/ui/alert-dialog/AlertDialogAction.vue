<script setup lang="ts">
import { AlertDialogAction as RekaAction, type AlertDialogActionProps } from "reka-ui";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";
const props = defineProps<AlertDialogActionProps & { class?: string }>();
</script>
<template><RekaAction v-bind="props" :class="cn(buttonVariants(), props.class)"><slot /></RekaAction></template>
