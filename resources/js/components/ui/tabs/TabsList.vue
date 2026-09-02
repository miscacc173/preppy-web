<script setup lang="ts">
import { TabsList as RekaList, type TabsListProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<TabsListProps & { class?: string }>();
</script>
<template>
  <RekaList v-bind="props" :class="cn('inline-flex h-9 items-center justify-center rounded-lg bg-muted p-1 text-muted-foreground', props.class)">
    <slot />
  </RekaList>
</template>
