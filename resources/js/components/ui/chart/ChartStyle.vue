<script setup lang="ts">
import { computed } from "vue";
import type { ChartConfig } from ".";

const props = defineProps<{ id: string; config: ChartConfig }>();

const THEMES = { light: "", dark: ".dark" } as const;

const colorConfig = computed(() =>
  Object.entries(props.config).filter(([, cfg]) => cfg.theme || cfg.color),
);

const css = computed(() => {
  if (!colorConfig.value.length) return "";
  return Object.entries(THEMES)
    .map(([theme, prefix]) => {
      const body = colorConfig.value
        .map(([key, itemConfig]) => {
          const color =
            (itemConfig as any).theme?.[theme as keyof typeof THEMES] || itemConfig.color;
          return color ? `  --color-${key}: ${color};` : null;
        })
        .filter(Boolean)
        .join("\n");
      return `${prefix} [data-chart=${props.id}] {\n${body}\n}`;
    })
    .join("\n");
});
</script>

<template>
  <component :is="'style'" v-if="colorConfig.length">{{ css }}</component>
</template>
