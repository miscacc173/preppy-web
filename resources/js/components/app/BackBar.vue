<script setup lang="ts">
/**
 * Universal back chip. Uses router history when available; otherwise falls
 * back to `to`. Renders a 44px tap target.
 */
import { ArrowLeft } from "lucide-vue-next";
import { useRouter } from "vue-router";

const props = withDefaults(defineProps<{ to?: string; label?: string }>(), {
  to: "/dashboard",
  label: "Back",
});

const router = useRouter();

function onClick() {
  if (typeof window !== "undefined" && window.history.length > 1) {
    router.back();
  } else {
    router.push(props.to);
  }
}
</script>

<template>
  <div class="mb-4">
    <button
      type="button"
      class="inline-flex items-center gap-2 min-h-11 px-3 -ml-3 rounded-md text-[13px] font-medium text-ink2 hover:bg-bg-soft hover:text-ink transition"
      :aria-label="label"
      @click="onClick"
    >
      <ArrowLeft :size="16" />
      <span class="hidden sm:inline">{{ label }}</span>
    </button>
    <!-- Fallback link for assistive tech / no-JS -->
    <noscript>
      <RouterLink :to="to" class="sr-only">{{ label }}</RouterLink>
    </noscript>
  </div>
</template>
