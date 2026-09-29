<script setup lang="ts">
/**
 * Soft educational doodle background — pencils, rulers, atoms, books, math glyphs.
 * Absolutely positioned, pointer-events-none, ~6-9% opacity. Layered on page bodies.
 */
import { computed } from "vue";

const props = withDefaults(defineProps<{ density?: "sparse" | "normal" | "dense" }>(), {
  density: "normal",
});

const doodles = [
  { glyph: "✏️", left: "4%", top: "8%", rot: -18, size: 38 },
  { glyph: "📐", left: "92%", top: "12%", rot: 12, size: 44 },
  { glyph: "📓", left: "8%", top: "44%", rot: -8, size: 40 },
  { glyph: "🧮", left: "88%", top: "38%", rot: 6, size: 42 },
  { glyph: "📚", left: "3%", top: "76%", rot: 4, size: 46 },
  { glyph: "💡", left: "94%", top: "70%", rot: -12, size: 38 },
  { glyph: "⚛", left: "50%", top: "5%", rot: 0, size: 52 },
  { glyph: "🎓", left: "46%", top: "92%", rot: 8, size: 42 },
  { glyph: "∑", left: "15%", top: "22%", rot: -8, size: 54 },
  { glyph: "π", left: "78%", top: "58%", rot: 14, size: 58 },
  { glyph: "√", left: "22%", top: "62%", rot: -6, size: 46 },
  { glyph: "∫", left: "70%", top: "26%", rot: 8, size: 56 },
  { glyph: "📏", left: "30%", top: "85%", rot: 24, size: 36 },
  { glyph: "🗒", left: "62%", top: "78%", rot: -14, size: 38 },
];

const items = computed(() =>
  props.density === "sparse" ? doodles.slice(0, 8) : props.density === "dense" ? [...doodles, ...doodles] : doodles,
);
</script>

<template>
  <div aria-hidden class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <svg class="absolute inset-0 w-full h-full opacity-[0.045]" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="notebookLines" width="100%" height="32" patternUnits="userSpaceOnUse">
          <line x1="0" y1="31.5" x2="100%" y2="31.5" stroke="#0E1330" stroke-width="0.5" />
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#notebookLines)" />
    </svg>
    <div
      v-for="(d, i) in items"
      :key="i"
      class="absolute text-ink opacity-[0.07] select-none animate-float-slow"
      :style="{ left: d.left, top: d.top, transform: `rotate(${d.rot}deg)`, fontSize: `${d.size}px`, animationDelay: `${i * 0.7}s` }"
    >
      {{ d.glyph }}
    </div>
  </div>
</template>
