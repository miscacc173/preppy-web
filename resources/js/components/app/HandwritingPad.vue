<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Eraser, PenLine, Undo2 } from "lucide-vue-next";

interface Point { x: number; y: number }
interface Stroke { points: Point[]; color: string; width: number }

const props = withDefaults(
  defineProps<{
    value?: string;
    minHeight?: number;
    accent?: "violet" | "gold";
  }>(),
  { minHeight: 220, accent: "violet" },
);

const emit = defineEmits<{ "update:value": [v: string]; "strokes-change": [count: number] }>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const wrapRef = ref<HTMLDivElement | null>(null);
const drawing = ref(false);
const strokes = ref<Stroke[]>([]);
const tool = ref<"pen" | "eraser">("pen");

let ro: ResizeObserver | null = null;

function redraw() {
  const canvas = canvasRef.value;
  if (!canvas) return;
  const ctx = canvas.getContext("2d");
  if (!ctx) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.lineCap = "round";
  ctx.lineJoin = "round";
  for (const s of strokes.value) {
    ctx.strokeStyle = s.color;
    ctx.lineWidth = s.width;
    ctx.beginPath();
    s.points.forEach((p, i) => (i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y)));
    ctx.stroke();
  }
}

onMounted(() => {
  const canvas = canvasRef.value;
  const wrap = wrapRef.value;
  if (!canvas || !wrap) return;
  const dpr = window.devicePixelRatio || 1;
  ro = new ResizeObserver(() => {
    const rect = wrap.getBoundingClientRect();
    canvas.width = rect.width * dpr;
    canvas.height = Math.max(props.minHeight, rect.height) * dpr;
    canvas.style.width = `${rect.width}px`;
    canvas.style.height = `${Math.max(props.minHeight, rect.height)}px`;
    const ctx = canvas.getContext("2d");
    if (ctx) {
      ctx.scale(dpr, dpr);
      redraw();
    }
  });
  ro.observe(wrap);
});
onBeforeUnmount(() => ro?.disconnect());

watch(strokes, () => {
  redraw();
  emit("strokes-change", strokes.value.length);
  emit(
    "update:value",
    strokes.value.length ? `[Handwritten answer · ${strokes.value.length} stroke${strokes.value.length === 1 ? "" : "s"}]` : "",
  );
}, { deep: true });

function pos(e: PointerEvent): Point {
  const rect = canvasRef.value!.getBoundingClientRect();
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

function pointerDown(e: PointerEvent) {
  e.preventDefault();
  (e.target as HTMLCanvasElement).setPointerCapture(e.pointerId);
  drawing.value = true;
  const resolved =
    tool.value === "eraser" ? "#ffffff" : getComputedStyle(document.documentElement).getPropertyValue("--ink").trim() || "#0a0a1a";
  strokes.value = [...strokes.value, { points: [pos(e)], color: resolved, width: tool.value === "eraser" ? 18 : 2.2 }];
}

function pointerMove(e: PointerEvent) {
  if (!drawing.value) return;
  e.preventDefault();
  const next = strokes.value.slice();
  const last = next[next.length - 1];
  if (!last) return;
  next[next.length - 1] = { ...last, points: [...last.points, pos(e)] };
  strokes.value = next;
}

function pointerUp() {
  drawing.value = false;
}

function undo() {
  strokes.value = strokes.value.slice(0, -1);
}
function clear() {
  strokes.value = [];
}

const accentBtn = props.accent === "gold" ? "border-gold text-gold bg-gold-light" : "border-violet text-violet bg-violet-light";
</script>

<template>
  <div class="w-full">
    <div class="flex items-center gap-2 mb-2">
      <button
        type="button"
        :class="`inline-flex items-center gap-1.5 text-[12px] font-medium h-9 px-3 rounded-md border ${tool === 'pen' ? accentBtn : 'bg-bg-soft border-border text-ink2'}`"
        @click="tool = 'pen'"
      >
        <PenLine :size="13" /> Pen
      </button>
      <button
        type="button"
        :class="`inline-flex items-center gap-1.5 text-[12px] font-medium h-9 px-3 rounded-md border ${tool === 'eraser' ? accentBtn : 'bg-bg-soft border-border text-ink2'}`"
        @click="tool = 'eraser'"
      >
        <Eraser :size="13" /> Eraser
      </button>
      <div class="flex-1" />
      <button
        type="button"
        class="inline-flex items-center gap-1.5 text-[12px] font-medium h-9 px-3 rounded-md border bg-bg-soft border-border text-ink2 hover:bg-bg-soft2"
        @click="undo"
      >
        <Undo2 :size="13" /> Undo
      </button>
      <button
        type="button"
        class="text-[12px] font-medium h-9 px-3 rounded-md border bg-bg-soft border-border text-ink2 hover:bg-bg-soft2"
        @click="clear"
      >
        Clear
      </button>
    </div>
    <div
      ref="wrapRef"
      class="w-full bg-white rounded-lg border border-border overflow-hidden"
      :style="{ minHeight: `${minHeight}px`, backgroundImage: 'linear-gradient(to bottom, transparent 27px, #e5e7eb 28px)', backgroundSize: '100% 28px' }"
    >
      <canvas
        ref="canvasRef"
        class="block touch-none cursor-crosshair"
        @pointerdown="pointerDown"
        @pointermove="pointerMove"
        @pointerup="pointerUp"
        @pointercancel="pointerUp"
        @pointerleave="pointerUp"
      />
    </div>
    <p class="text-[11px] text-ink3 mt-2">
      Handwritten answers simulate exam conditions. Strokes are captured on the pad above.
    </p>
  </div>
</template>
