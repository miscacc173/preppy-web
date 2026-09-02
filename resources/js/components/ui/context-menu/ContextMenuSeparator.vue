<script setup lang="ts">
import { ContextMenuSeparator as RekaSeparator, type ContextMenuSeparatorProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<ContextMenuSeparatorProps & { class?: string }>();
</script>
<template>
  <RekaSeparator v-bind="props" :class="cn('-mx-1 my-1 h-px bg-border', props.class)" />
</template>
