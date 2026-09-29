<script setup lang="ts">
/**
 * Lightweight chord-style diagram. Arc length per node is proportional
 * to its value; ribbons connect every pair with opacity proportional to
 * the geometric mean of the two endpoint values.
 */
import { computed, ref } from "vue";

export interface ChordNode {
  label: string;
  value: number;
  color: string;
}

const props = withDefaults(defineProps<{ nodes: ChordNode[]; size?: number }>(), { size: 360 });

const hover = ref<number | null>(null);
const cx = computed(() => props.size / 2);
const cy = computed(() => props.size / 2);
const rOuter = computed(() => props.size * 0.38);
const rInner = computed(() => props.size * 0.34);
const rRibbon = computed(() => props.size * 0.33);

function polar(r: number, a: number): [number, number] {
  return [cx.value + r * Math.cos(a), cy.value + r * Math.sin(a)];
}

const arcs = computed(() => {
  const total = props.nodes.reduce((s, n) => s + Math.max(n.value, 1), 0);
  const gap = 0.04;
  const totalGap = gap * props.nodes.length;
  const available = Math.PI * 2 - totalGap;
  let cursor = -Math.PI / 2;
  return props.nodes.map((n) => {
    const span = (Math.max(n.value, 1) / total) * available;
    const start = cursor;
    const end = cursor + span;
    cursor = end + gap;
    const mid = (start + end) / 2;
    return { ...n, start, end, mid };
  });
});

function arcPath(r: number, a0: number, a1: number) {
  const [x0, y0] = polar(r, a0);
  const [x1, y1] = polar(r, a1);
  const large = a1 - a0 > Math.PI ? 1 : 0;
  return `M ${x0} ${y0} A ${r} ${r} 0 ${large} 1 ${x1} ${y1}`;
}

function bandPath(r: number, a0: number, a1: number) {
  const [x0, y0] = polar(r, a0);
  const [x1, y1] = polar(r, a1);
  const innerR = r - (rOuter.value - rInner.value);
  const [xi1, yi1] = polar(innerR, a1);
  const [xi0, yi0] = polar(innerR, a0);
  const large = a1 - a0 > Math.PI ? 1 : 0;
  return `M ${x0} ${y0} A ${r} ${r} 0 ${large} 1 ${x1} ${y1} L ${xi1} ${yi1} A ${innerR} ${innerR} 0 ${large} 0 ${xi0} ${yi0} Z`;
}

const ribbons = computed(() => {
  const list: { d: string; color: string; key: string; opacity: number; i: number; j: number }[] = [];
  const arcList = arcs.value;
  for (let i = 0; i < arcList.length; i++) {
    for (let j = i + 1; j < arcList.length; j++) {
      const a = arcList[i];
      const b = arcList[j];
      const w = Math.min(0.06, Math.min(a.end - a.start, b.end - b.start) * 0.4);
      const [ax0, ay0] = polar(rRibbon.value, a.mid - w);
      const [ax1, ay1] = polar(rRibbon.value, a.mid + w);
      const [bx0, by0] = polar(rRibbon.value, b.mid - w);
      const [bx1, by1] = polar(rRibbon.value, b.mid + w);
      const d = `M ${ax0} ${ay0} Q ${cx.value} ${cy.value} ${bx0} ${by0} A ${rRibbon.value} ${rRibbon.value} 0 0 1 ${bx1} ${by1} Q ${cx.value} ${cy.value} ${ax1} ${ay1} A ${rRibbon.value} ${rRibbon.value} 0 0 1 ${ax0} ${ay0} Z`;
      const intensity = Math.sqrt((a.value / 100) * (b.value / 100));
      list.push({ d, color: i % 2 === 0 ? a.color : b.color, key: `${i}-${j}`, opacity: 0.12 + intensity * 0.35, i, j });
    }
  }
  return list;
});

function labelPos(mid: number) {
  const labelR = rOuter.value + 16;
  const [lx, ly] = polar(labelR, mid);
  const anchor = Math.cos(mid) > 0.2 ? "start" : Math.cos(mid) < -0.2 ? "end" : "middle";
  return { lx, ly, anchor };
}
</script>

<template>
  <div class="w-full grid place-items-center">
    <svg :viewBox="`0 0 ${size} ${size}`" class="w-full max-w-[420px] h-auto">
      <!-- ribbons -->
      <g>
        <path
          v-for="r in ribbons"
          :key="r.key"
          :d="r.d"
          :fill="r.color"
          :opacity="hover != null && r.i !== hover && r.j !== hover ? 0.05 : r.opacity"
          style="transition: opacity 220ms"
        />
      </g>
      <!-- arcs -->
      <g>
        <g
          v-for="(a, i) in arcs"
          :key="a.label"
          style="cursor: pointer"
          @mouseenter="hover = i"
          @mouseleave="hover = null"
        >
          <path :d="bandPath(rOuter, a.start, a.end)" :fill="a.color" :opacity="hover === null || hover === i ? 1 : 0.4" />
          <text
            :x="labelPos(a.mid).lx"
            :y="labelPos(a.mid).ly"
            :text-anchor="labelPos(a.mid).anchor"
            dominant-baseline="middle"
            class="fill-ink"
            style="font-size: 11px; font-weight: 600"
          >
            {{ a.label }}
          </text>
          <text
            :x="labelPos(a.mid).lx"
            :y="labelPos(a.mid).ly + 12"
            :text-anchor="labelPos(a.mid).anchor"
            dominant-baseline="middle"
            class="fill-ink3"
            style="font-size: 10px; font-family: var(--font-mono, ui-monospace)"
          >
            {{ a.value }}%
          </text>
          <path :d="arcPath(rInner, a.start, a.end)" fill="none" />
        </g>
      </g>
    </svg>
  </div>
</template>
