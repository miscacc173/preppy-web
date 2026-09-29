<script setup lang="ts">
import { useRouter } from "vue-router";

const props = withDefaults(defineProps<{ size?: number }>(), { size: 28 });
const router = useRouter();

function handleClick(e: MouseEvent) {
  e.preventDefault();
  router.push("/");
}
</script>

<template>
  <a
    href="/"
    class="inline-flex items-center gap-2 text-[15px] font-bold text-ink tracking-[-0.02em] no-underline"
    @click="handleClick"
  >
    <span class="grid place-items-center rounded-[8px] bg-blue text-white" :style="{ width: `${size}px`, height: `${size}px` }">
      <svg :width="Math.round(size * 0.5)" :height="Math.round(size * 0.5)" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="2,10 5,6 8,8 12,3" />
      </svg>
    </span>
    Preppy
  </a>
</template>
