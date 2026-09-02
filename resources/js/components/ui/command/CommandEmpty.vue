<script setup lang="ts">
import { inject } from "vue";
import { COMMAND_CONTEXT_KEY } from "./context";

const ctx = inject(COMMAND_CONTEXT_KEY);
</script>

<template>
  <div v-if="ctx && ctx.visibleCount.value === 0" class="py-6 text-center text-sm">
    <slot />
  </div>
</template>
