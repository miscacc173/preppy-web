<script setup lang="ts">
import { computed, reactive } from "vue";
import MiniInput from "./MiniInput.vue";
import type { AssignData } from "./assign-types";

const props = withDefaults(
  defineProps<{ defaults: AssignData; labelPrimary?: string; title?: string }>(),
  { labelPrimary: "Assign to Students", title: "Assign to students" },
);
const emit = defineEmits<{ close: []; assign: [d: AssignData] }>();

const d = reactive<AssignData>({ ...props.defaults });
const disabled = computed(() => !d.className.trim());
</script>

<template>
  <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 grid place-items-center p-4">
    <div class="bg-bg-card border border-border rounded-2xl w-full max-w-[480px] p-5 shadow-[0_24px_60px_rgba(15,23,42,0.2)]">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div>
          <h3 class="text-[16px] font-bold text-ink tracking-[-0.01em]">{{ title }}</h3>
          <p class="text-[12.5px] text-ink3 font-light mt-1">Choose the class, timing and instructions.</p>
        </div>
        <button class="text-ink3 hover:text-ink text-xl leading-none" @click="emit('close')">×</button>
      </div>
      <div class="space-y-3">
        <MiniInput v-model="d.className" label="Class or student group" />
        <div class="grid grid-cols-2 gap-2">
          <MiniInput v-model="d.startDate" label="Start date" type="date" />
          <MiniInput v-model="d.dueDate" label="Due date" type="date" />
        </div>
        <label class="block">
          <div class="text-[11px] font-semibold text-ink3 mb-0.5">Student instructions</div>
          <textarea
            v-model="d.instructions"
            placeholder="Optional message shown to students when they start."
            class="w-full bg-bg-card border border-border rounded-md px-2 py-1.5 text-[12.5px] text-ink outline-none focus:border-violet-300 min-h-[72px]"
          />
        </label>
        <div class="flex items-center gap-3">
          <label class="inline-flex items-center gap-2 text-[12.5px] text-ink2">
            <input v-model="d.timed" type="checkbox" /> Timed
          </label>
          <label class="inline-flex items-center gap-2 text-[12.5px] text-ink2">
            <input v-model="d.aiFeedback" type="checkbox" /> AI feedback for students
          </label>
        </div>
      </div>
      <div class="flex justify-end gap-2 mt-5">
        <button class="text-[13px] font-semibold text-ink2 px-4 py-2 rounded-full hover:bg-bg-soft" @click="emit('close')">Cancel</button>
        <button
          :disabled="disabled"
          class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13.5px] font-semibold px-4 py-2 rounded-full hover:bg-violet-700 disabled:opacity-50 disabled:pointer-events-none transition"
          @click="emit('assign', { ...d })"
        >
          {{ labelPrimary }}
        </button>
      </div>
    </div>
  </div>
</template>
