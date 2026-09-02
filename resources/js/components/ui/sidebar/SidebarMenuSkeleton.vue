<script setup lang="ts">
import { cn } from "@/lib/utils";
import { Skeleton } from "@/components/ui/skeleton";

const props = withDefaults(defineProps<{ class?: string; showIcon?: boolean }>(), { showIcon: false });

// Random width between 50 and 90%, picked once per mount (matches source's useMemo(() => ..., [])).
const width = `${Math.floor(Math.random() * 40) + 50}%`;
</script>
<template>
  <div data-sidebar="menu-skeleton" :class="cn('flex h-8 items-center gap-2 rounded-md px-2', props.class)">
    <Skeleton v-if="showIcon" class="size-4 rounded-md" data-sidebar="menu-skeleton-icon" />
    <Skeleton class="h-4 max-w-(--skeleton-width) flex-1" data-sidebar="menu-skeleton-text" :style="{ '--skeleton-width': width }" />
  </div>
</template>
