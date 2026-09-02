<script setup lang="ts">
import { TabsContent as RekaContent, type TabsContentProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<TabsContentProps & { class?: string }>();
</script>
<template>
  <RekaContent v-bind="props" :class="cn('mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2', props.class)">
    <slot />
  </RekaContent>
</template>
