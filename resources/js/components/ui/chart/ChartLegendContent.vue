<script setup lang="ts">
import { computed, inject } from "vue";
import { cn } from "@/lib/utils";
import { CHART_CONTEXT_KEY } from "./context";
import { getPayloadConfigFromPayload } from ".";

const props = withDefaults(
  defineProps<{
    class?: string;
    hideIcon?: boolean;
    payload?: any[];
    verticalAlign?: "top" | "bottom";
    nameKey?: string;
  }>(),
  { verticalAlign: "bottom" },
);

const ctx = inject(CHART_CONTEXT_KEY)!;
const items = computed(() => (props.payload ?? []).filter((item) => item.type !== "none"));
</script>

<template>
  <div
    v-if="items.length"
    :class="cn('flex items-center justify-center gap-4', verticalAlign === 'top' ? 'pb-3' : 'pt-3', props.class)"
  >
    <div
      v-for="item in items"
      :key="item.value"
      class="flex items-center gap-1.5 [&>svg]:h-3 [&>svg]:w-3 [&>svg]:text-muted-foreground"
    >
      <component
        :is="getPayloadConfigFromPayload(ctx.config, item, `${nameKey || item.dataKey || 'value'}`)?.icon"
        v-if="!hideIcon && getPayloadConfigFromPayload(ctx.config, item, `${nameKey || item.dataKey || 'value'}`)?.icon"
      />
      <div v-else class="h-2 w-2 shrink-0 rounded-[2px]" :style="{ backgroundColor: item.color }" />
      {{ getPayloadConfigFromPayload(ctx.config, item, `${nameKey || item.dataKey || 'value'}`)?.label }}
    </div>
  </div>
</template>
