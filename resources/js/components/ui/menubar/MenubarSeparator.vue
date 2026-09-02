<script setup lang="ts">
import { MenubarSeparator as RekaSeparator, type MenubarSeparatorProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<MenubarSeparatorProps & { class?: string }>();
</script>
<template>
  <RekaSeparator v-bind="props" :class="cn('-mx-1 my-1 h-px bg-muted', props.class)" />
</template>
