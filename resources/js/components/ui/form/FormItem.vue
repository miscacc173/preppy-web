<script setup lang="ts">
import { provide, useId } from "vue";
import { cn } from "@/lib/utils";
import { FORM_ITEM_CONTEXT_KEY } from "./context";

const props = defineProps<{ class?: string }>();

const id = useId();
provide(FORM_ITEM_CONTEXT_KEY, { id });
</script>

<template>
  <div :class="cn('space-y-2', props.class)">
    <slot />
  </div>
</template>
