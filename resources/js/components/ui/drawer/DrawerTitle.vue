<script setup lang="ts">
import { DrawerTitle as VaulTitle, type DrawerTitleProps } from "vaul-vue";
import { cn } from "@/lib/utils";
const props = defineProps<DrawerTitleProps & { class?: string }>();
</script>
<template><VaulTitle v-bind="props" :class="cn('text-lg font-semibold leading-none tracking-tight', props.class)"><slot /></VaulTitle></template>
