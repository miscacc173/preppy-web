<script setup lang="ts">
import { cn } from "@/lib/utils";
const props = defineProps<{ class?: string; heading?: string }>();
</script>

<template>
  <div
    cmdk-group=""
    :class="cn('overflow-hidden p-1 text-foreground [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:py-1.5 [&_[cmdk-group-heading]]:text-xs [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group-heading]]:text-muted-foreground', props.class)"
  >
    <div v-if="heading" cmdk-group-heading="">{{ heading }}</div>
    <slot />
  </div>
</template>
