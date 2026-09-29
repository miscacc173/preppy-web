<script setup lang="ts">
import { cn } from "@/lib/utils";
import { toneMap, type Tone } from "./toneMap";
import Reveal from "./Reveal.vue";
import MockupFrame from "./MockupFrame.vue";

defineProps<{
  label: string;
  title: string;
  description: string;
  bullets: { icon: unknown; text: string }[];
  icon: unknown;
  tone: Tone;
  reverse?: boolean;
}>();
</script>

<template>
  <section class="relative py-20 md:py-28 overflow-hidden">
    <!-- Ambient tone glow -->
    <div
      aria-hidden
      :class="
        cn(
          'pointer-events-none absolute inset-0 -z-10 opacity-60 to-transparent',
          `bg-[radial-gradient(60%_50%_at_${reverse ? '20%' : '80%'}_50%,var(--tw-gradient-stops))]`,
          toneMap[tone].glow,
        )
      "
    />
    <div class="max-w-[1180px] mx-auto px-5">
      <div :class="cn('grid gap-12 md:gap-16 lg:grid-cols-2 items-center', reverse && 'lg:[&>*:first-child]:order-2')">
        <!-- Copy -->
        <Reveal>
          <div class="flex items-center gap-2 mb-5">
            <span :class="`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-mono uppercase tracking-[0.14em] ${toneMap[tone].chip}`">
              <span :class="`w-1.5 h-1.5 rounded-full ${toneMap[tone].dot} animate-pulse`" />
              {{ label }}
            </span>
          </div>

          <div class="flex items-start gap-4 mb-4">
            <span :class="`shrink-0 grid place-items-center w-11 h-11 rounded-xl ${toneMap[tone].iconBg} shadow-[inset_0_0_0_1px_rgba(255,255,255,0.4)]`">
              <component :is="icon" :class="toneMap[tone].icon" :size="20" :stroke-width="2" />
            </span>
            <h2 class="text-[30px] md:text-[38px] font-bold text-ink tracking-[-0.028em] leading-[1.1]">{{ title }}</h2>
          </div>

          <p class="text-[15.5px] md:text-[16px] text-ink3 font-light leading-relaxed max-w-[520px] mb-7">{{ description }}</p>

          <ul class="space-y-3">
            <li v-for="b in bullets" :key="b.text" class="flex items-start gap-3 text-[14px] text-ink2">
              <span :class="`shrink-0 grid place-items-center w-6 h-6 rounded-md ${toneMap[tone].iconBg}`">
                <component :is="b.icon" :size="13" :class="toneMap[tone].icon" :stroke-width="2.5" />
              </span>
              <span class="pt-0.5">{{ b.text }}</span>
            </li>
          </ul>
        </Reveal>

        <!-- Preview -->
        <Reveal :delay="0.15">
          <MockupFrame :tone="tone">
            <slot name="preview" />
          </MockupFrame>
        </Reveal>
      </div>
    </div>
  </section>
</template>
