<script setup lang="ts">
import { onErrorCaptured, ref } from "vue";
import { Toaster } from "@/components/ui/sonner";
import ErrorView from "@/views/ErrorView.vue";

const caughtError = ref<Error | null>(null);

onErrorCaptured((err) => {
  caughtError.value = err instanceof Error ? err : new Error(String(err));
  return false; // stop propagation, we're handling it
});

function reset() {
  caughtError.value = null;
}
</script>

<template>
  <ErrorView v-if="caughtError" :error="caughtError" @reset="reset" />
  <template v-else>
    <RouterView />
    <Toaster />
  </template>
</template>
