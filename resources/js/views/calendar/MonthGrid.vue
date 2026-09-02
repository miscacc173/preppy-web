<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ month: number; year: number }>();

const cells = computed<(number | null)[]>(() => {
  const first = new Date(props.year, props.month, 1).getDay();
  const days = new Date(props.year, props.month + 1, 0).getDate();
  const c: (number | null)[] = [];
  for (let i = 0; i < first; i++) c.push(null);
  for (let d = 1; d <= days; d++) c.push(d);
  return c;
});
const examDays = new Set([15, 20, 28]);
const WEEKDAYS = ["S", "M", "T", "W", "T", "F", "S"];
</script>

<template>
  <div>
    <div class="grid grid-cols-7 gap-1 mb-2">
      <div v-for="(d, i) in WEEKDAYS" :key="i" class="text-[11px] text-ink4 font-semibold text-center">{{ d }}</div>
    </div>
    <div class="grid grid-cols-7 gap-1">
      <div
        v-for="(c, i) in cells"
        :key="i"
        :class="`aspect-square rounded-md text-[12px] grid place-items-center border ${
          c === null ? 'border-transparent' : examDays.has(c) ? 'bg-pink-light text-pink border-[#F8D0D0] font-semibold' : 'bg-bg-soft border-border text-ink2 hover:bg-bg-soft2'
        }`"
      >
        {{ c ?? "" }}
      </div>
    </div>
  </div>
</template>
