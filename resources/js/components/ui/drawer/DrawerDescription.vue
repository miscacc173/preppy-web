<script setup lang="ts">
import { DrawerDescription as VaulDescription, type DrawerDescriptionProps } from "vaul-vue";
import { cn } from "@/lib/utils";
const props = defineProps<DrawerDescriptionProps & { class?: string }>();
</script>
<template><VaulDescription v-bind="props" :class="cn('text-sm text-muted-foreground', props.class)"><slot /></VaulDescription></template>
