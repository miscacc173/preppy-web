<script setup lang="ts">
import { AlertDialogCancel as RekaCancel, type AlertDialogCancelProps } from "reka-ui";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";
const props = defineProps<AlertDialogCancelProps & { class?: string }>();
</script>
<template><RekaCancel v-bind="props" :class="cn(buttonVariants({ variant: 'outline' }), 'mt-2 sm:mt-0', props.class)"><slot /></RekaCancel></template>
