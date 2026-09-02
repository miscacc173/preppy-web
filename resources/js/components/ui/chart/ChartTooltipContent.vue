<script setup lang="ts">
import { computed, inject } from "vue";
import { cn } from "@/lib/utils";
import { CHART_CONTEXT_KEY } from "./context";
import { getPayloadConfigFromPayload } from ".";

const props = withDefaults(
  defineProps<{
    active?: boolean;
    payload?: any[];
    class?: string;
    indicator?: "line" | "dot" | "dashed";
    hideLabel?: boolean;
    hideIndicator?: boolean;
    label?: string;
    labelFormatter?: (value: unknown, payload: any[]) => string;
    labelClassName?: string;
    formatter?: (value: unknown, name: string, item: any, index: number, payload: unknown) => string;
    color?: string;
    nameKey?: string;
    labelKey?: string;
  }>(),
  { indicator: "dot" },
);

const ctx = inject(CHART_CONTEXT_KEY)!;

const tooltipLabel = computed(() => {
  if (props.hideLabel || !props.payload?.length) return null;
  const [item] = props.payload;
  const key = `${props.labelKey || item?.dataKey || item?.name || "value"}`;
  const itemConfig = getPayloadConfigFromPayload(ctx.config, item, key);
  const value =
    !props.labelKey && typeof props.label === "string"
      ? ctx.config[props.label as keyof typeof ctx.config]?.label || props.label
      : itemConfig?.label;
  if (props.labelFormatter) return props.labelFormatter(value, props.payload);
  return value ?? null;
});

const nestLabel = computed(() => (props.payload?.length ?? 0) === 1 && props.indicator !== "dot");

const items = computed(() => (props.payload ?? []).filter((item) => item.type !== "none"));
</script>

<template>
  <div
    v-if="active && payload?.length"
    :class="
      cn(
        'grid min-w-[8rem] items-start gap-1.5 rounded-lg border border-border/50 bg-background px-2.5 py-1.5 text-xs shadow-xl',
        props.class,
      )
    "
  >
    <div v-if="!nestLabel && tooltipLabel" :class="cn('font-medium', labelClassName)">{{ tooltipLabel }}</div>
    <div class="grid gap-1.5">
      <div
        v-for="(item, index) in items"
        :key="item.dataKey"
        :class="cn('flex w-full flex-wrap items-stretch gap-2 [&>svg]:h-2.5 [&>svg]:w-2.5 [&>svg]:text-muted-foreground', indicator === 'dot' && 'items-center')"
      >
        <template v-if="formatter && item?.value !== undefined && item.name">
          {{ formatter(item.value, item.name, item, index, item.payload) }}
        </template>
        <template v-else>
          <component
            :is="getPayloadConfigFromPayload(ctx.config, item, `${nameKey || item.name || item.dataKey || 'value'}`)?.icon"
            v-if="getPayloadConfigFromPayload(ctx.config, item, `${nameKey || item.name || item.dataKey || 'value'}`)?.icon"
          />
          <div
            v-else-if="!hideIndicator"
            :class="
              cn('shrink-0 rounded-[2px] border-(--color-border) bg-(--color-bg)', {
                'h-2.5 w-2.5': indicator === 'dot',
                'w-1': indicator === 'line',
                'w-0 border-[1.5px] border-dashed bg-transparent': indicator === 'dashed',
                'my-0.5': nestLabel && indicator === 'dashed',
              })
            "
            :style="{ '--color-bg': color || item.payload?.fill || item.color, '--color-border': color || item.payload?.fill || item.color }"
          />
          <div :class="cn('flex flex-1 justify-between leading-none', nestLabel ? 'items-end' : 'items-center')">
            <div class="grid gap-1.5">
              <div v-if="nestLabel && tooltipLabel">{{ tooltipLabel }}</div>
              <span class="text-muted-foreground">
                {{ getPayloadConfigFromPayload(ctx.config, item, `${nameKey || item.name || item.dataKey || 'value'}`)?.label || item.name }}
              </span>
            </div>
            <span v-if="item.value" class="font-mono font-medium tabular-nums text-foreground">
              {{ typeof item.value === 'number' ? item.value.toLocaleString() : item.value }}
            </span>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
