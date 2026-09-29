<script setup lang="ts">
import { colorMap, type BarRow } from ".";

defineProps<{ rows: BarRow[] }>();
</script>

<template>
  <div class="flex flex-col gap-2.5">
    <div v-for="r in rows" :key="r.label" class="flex items-center gap-2.5">
      <span class="text-[12px] text-ink2 w-24 shrink-0">{{ r.label }}</span>
      <div class="flex-1 h-[5px] bg-bg-soft2 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-[width] duration-700"
          :style="{ width: `${r.value}%`, background: colorMap[r.color ?? 'mint'] }"
        />
      </div>
      <span class="font-mono text-[11px] text-ink3 w-7 text-right shrink-0">{{ r.value }}</span>
    </div>
  </div>
</template>
