<script setup lang="ts">
/**
 * DEVIATION FROM SOURCE: the React version uses `framer-motion`'s
 * `whileInView` + `Variants` (fadeUp/stagger) for scroll-triggered reveals.
 * There's no framer-motion in this Vue project (see AnimatedButton's note in
 * Phase 2 for the same call) — this replicates the same fade-up-on-scroll
 * effect with a plain IntersectionObserver + CSS transition instead. Visual
 * result is the same; what's lost is framer-motion's spring/stagger easing
 * primitives, so nested "stagger children" lists animate together as one
 * block rather than each child offsetting by ~120ms — a minor difference,
 * flag it if you want the staggered version specifically.
 */
import { onBeforeUnmount, onMounted, ref } from "vue";

withDefaults(defineProps<{ delay?: number }>(), { delay: 0 });

const el = ref<HTMLElement | null>(null);
const visible = ref(false);
let observer: IntersectionObserver | null = null;

onMounted(() => {
  if (!el.value) return;
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          visible.value = true;
          observer?.disconnect();
        }
      });
    },
    { threshold: 0.15, rootMargin: "-80px" },
  );
  observer.observe(el.value);
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
  <div
    ref="el"
    class="transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
    :style="{ transitionDelay: `${delay}s` }"
  >
    <slot />
  </div>
</template>
