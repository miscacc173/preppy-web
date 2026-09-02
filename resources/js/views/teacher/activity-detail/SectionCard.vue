<script setup lang="ts">
import { ArrowUp, ArrowDown, Trash2, RefreshCw } from "lucide-vue-next";
import type { ActivitySection } from "@/lib/teacherStore";

const props = defineProps<{ index: number; total: number; s: ActivitySection; mode: "teacher" | "student" }>();
const emit = defineEmits<{
  "update:s": [s: ActivitySection];
  move: [dir: -1 | 1];
  delete: [];
  regenerate: [];
}>();
</script>

<template>
  <div class="bg-bg-card border border-border rounded-[14px] p-4 md:p-5">
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-lg bg-violet-50 grid place-items-center shrink-0 text-[12.5px] font-bold text-violet-700">{{ index + 1 }}</div>
      <div class="flex-1 min-w-0">
        <h3 v-if="mode === 'student'" class="text-[15px] font-bold text-ink tracking-[-0.01em]">{{ s.title }}</h3>
        <input
          v-else
          :value="s.title"
          class="w-full bg-transparent text-[15px] font-bold text-ink tracking-[-0.01em] outline-none focus:bg-bg-soft rounded px-1 -mx-1"
          @input="emit('update:s', { ...s, title: ($event.target as HTMLInputElement).value })"
        />
        <p v-if="mode === 'student'" class="text-[13.5px] text-ink2 leading-[1.6] mt-1 whitespace-pre-wrap">{{ s.body }}</p>
        <textarea
          v-else
          :value="s.body"
          class="w-full mt-1 bg-bg-soft/50 border border-transparent hover:border-border focus:border-violet-300 focus:bg-bg-card rounded-lg px-3 py-2 text-[13.5px] text-ink2 leading-[1.6] outline-none min-h-[70px]"
          @input="emit('update:s', { ...s, body: ($event.target as HTMLTextAreaElement).value })"
        />
      </div>
      <div v-if="mode !== 'student'" class="flex flex-col gap-1 shrink-0">
        <button title="Move up" :disabled="index === 0" class="w-7 h-7 rounded-md border border-border bg-bg-card grid place-items-center text-ink2 hover:border-violet-300 hover:text-violet-700 disabled:opacity-40 disabled:pointer-events-none" @click="emit('move', -1)">
          <ArrowUp :size="13" />
        </button>
        <button title="Move down" :disabled="index === total - 1" class="w-7 h-7 rounded-md border border-border bg-bg-card grid place-items-center text-ink2 hover:border-violet-300 hover:text-violet-700 disabled:opacity-40 disabled:pointer-events-none" @click="emit('move', 1)">
          <ArrowDown :size="13" />
        </button>
      </div>
    </div>
    <div v-if="mode !== 'student'" class="mt-3 flex justify-end gap-1.5">
      <button class="inline-flex items-center gap-1 text-[11.5px] font-semibold px-2 py-1 rounded-md border border-border text-ink2 hover:border-violet-300 hover:text-violet-700 transition" @click="emit('regenerate')">
        <RefreshCw :size="12" /> Regenerate
      </button>
      <button class="inline-flex items-center gap-1 text-[11.5px] font-semibold px-2 py-1 rounded-md border border-border text-red-600 hover:bg-red-50 hover:border-red-200 transition" @click="emit('delete')">
        <Trash2 :size="12" /> Remove
      </button>
    </div>
  </div>
</template>
