<script setup lang="ts">
/**
 * Floating dot animations are always-on CSS keyframes (matching the source's
 * framer-motion `animate={{ y: [...] }}` infinite loops). The hover
 * lift+scale uses a CSS transition instead of framer-motion's spring.
 */
import { toneMap, type Tone } from "./toneMap";

const props = defineProps<{ tone: Tone }>();
</script>

<template>
  <div class="relative transition-transform duration-300 ease-out hover:-translate-y-1.5 hover:scale-[1.01]">
    <!-- Outer glow -->
    <div aria-hidden :class="`absolute -inset-8 -z-10 rounded-[36px] blur-3xl opacity-70 bg-gradient-to-br ${toneMap[tone].glow} to-transparent`" />
    <!-- Floating decorative dots -->
    <span aria-hidden :class="`absolute -top-3 -right-3 w-2.5 h-2.5 rounded-full ${toneMap[tone].dot} opacity-70 animate-float-dot-a`" />
    <span aria-hidden :class="`absolute -bottom-4 -left-2 w-1.5 h-1.5 rounded-full ${toneMap[tone].dot} opacity-50 animate-float-dot-b`" />

    <div class="rounded-2xl border border-border bg-bg-card shadow-[0_20px_60px_-20px_rgba(14,19,48,0.25)] overflow-hidden transition-shadow duration-500 hover:shadow-[0_30px_80px_-20px_rgba(14,19,48,0.35)]">
      <!-- Browser chrome -->
      <div class="flex items-center gap-3 px-4 h-9 border-b border-border bg-bg-soft/70">
        <div class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-[#ff5f57]" />
          <span class="w-2.5 h-2.5 rounded-full bg-[#febc2e]" />
          <span class="w-2.5 h-2.5 rounded-full bg-[#28c840]" />
        </div>
        <div class="mx-auto flex items-center gap-2 text-[10.5px] font-mono text-ink4 bg-bg border border-border rounded-md px-2.5 py-0.5">
          <span class="w-1.5 h-1.5 rounded-full bg-mint" /> aistarprep.com
        </div>
      </div>
      <div class="p-5 min-h-[340px] bg-bg-card">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes float-dot-a {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
@keyframes float-dot-b {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(6px); }
}
.animate-float-dot-a { animation: float-dot-a 3.5s ease-in-out infinite; }
.animate-float-dot-b { animation: float-dot-b 4.2s ease-in-out infinite; }
@media (prefers-reduced-motion: reduce) {
  .animate-float-dot-a, .animate-float-dot-b { animation: none; }
}
</style>
