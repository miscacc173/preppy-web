<script setup lang="ts">
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { reportLovableError } from "@/lib/lovable-error-reporting";

const props = defineProps<{ error: Error }>();
const emit = defineEmits<{ reset: [] }>();

const router = useRouter();

onMounted(() => {
  console.error(props.error);
  reportLovableError(props.error, { boundary: "vue_root_error_view" });
});

function tryAgain() {
  emit("reset");
  router.go(0);
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-bg px-4">
    <div class="max-w-md text-center">
      <h1 class="text-xl font-semibold text-ink">Something interrupted this session</h1>
      <p class="mt-2 text-sm text-ink3">We've logged it. Try again or head back home.</p>
      <div class="mt-6 flex flex-wrap justify-center gap-2">
        <button
          class="inline-flex h-10 items-center justify-center rounded-md bg-blue px-5 text-sm font-medium text-white hover:opacity-90"
          @click="tryAgain"
        >
          Try again
        </button>
        <a
          href="/"
          class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-bg-card px-5 text-sm font-medium text-ink hover:bg-bg-soft"
        >
          Go home
        </a>
      </div>
    </div>
  </div>
</template>
