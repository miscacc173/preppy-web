<script setup lang="ts">
import { computed } from "vue";
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";

const props = defineProps<{ rows: string[][]; class?: string }>();

const safeRows = computed(() => (props.rows.length ? props.rows : [["—"]]));
const cols = computed(() => safeRows.value[0].length);
const colW = computed(() => 440 / cols.value);
const rowH = 30;
const height = computed(() => 20 + safeRows.value.length * rowH);
</script>

<template>
  <Frame :vb="`0 0 480 ${height + 20}`" :class="$props.class">
    <g v-for="(r, ri) in safeRows" :key="ri">
      <g v-for="(cell, ci) in r" :key="ci">
        <rect
          :x="20 + ci * colW"
          :y="20 + ri * rowH"
          :width="colW"
          :height="rowH"
          :fill="ri === 0 ? ACCENT : 'var(--bg-soft)'"
          :opacity="ri === 0 ? 0.18 : 1"
          :stroke="STROKE"
        />
        <text
          :x="20 + ci * colW + colW / 2"
          :y="20 + ri * rowH + rowH / 2 + 4"
          text-anchor="middle"
          font-size="12"
          :fill="STROKE"
          :font-weight="ri === 0 ? 600 : 400"
        >
          {{ cell }}
        </text>
      </g>
    </g>
  </Frame>
</template>
