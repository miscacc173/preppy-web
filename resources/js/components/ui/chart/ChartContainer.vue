<script setup lang="ts">
import { provide, useId } from "vue";
import { cn } from "@/lib/utils";
import { CHART_CONTEXT_KEY } from "./context";
import ChartStyle from "./ChartStyle.vue";
import type { ChartConfig } from ".";

const props = defineProps<{ id?: string; config: ChartConfig; class?: string }>();
const uid = useId();
const chartId = `chart-${(props.id || uid).replace(/:/g, "")}`;
const chartClasses = "flex aspect-video justify-center text-xs [&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line]:stroke-border/50 [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-dot]:stroke-transparent [&_.recharts-layer]:outline-none [&_.recharts-polar-grid]:stroke-border [&_.recharts-radial-bar-background-sector]:fill-muted [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-reference-line]:stroke-border [&_.recharts-sector]:stroke-transparent [&_.recharts-sector]:outline-none [&_.recharts-surface]:outline-none";
provide(CHART_CONTEXT_KEY, { config: props.config });
</script>

<template>
  <div :data-chart="chartId" :class="cn(chartClasses, props.class)">
    <ChartStyle :id="chartId" :config="config" />
    <slot />
  </div>
</template>
