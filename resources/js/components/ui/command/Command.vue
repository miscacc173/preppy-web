<script setup lang="ts">
import { provide, ref } from "vue";
import { cn } from "@/lib/utils";
import { COMMAND_CONTEXT_KEY } from "./context";

const props = defineProps<{ class?: string }>();

const search = ref("");
const visibleCount = ref(0);
provide(COMMAND_CONTEXT_KEY, { search, visibleCount });
</script>

<template>
  <div :class="cn('flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground', props.class)">
    <slot />
  </div>
</template>
