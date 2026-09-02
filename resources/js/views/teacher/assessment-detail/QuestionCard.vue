<script setup lang="ts">
import { ref } from "vue";
import { ArrowUp, ArrowDown, ChevronDown, RefreshCw, Copy, Trash2 } from "lucide-vue-next";
import type { AssessmentQuestion } from "@/lib/teacherStore";
import MetaChip from "./MetaChip.vue";
import IconBtn from "./IconBtn.vue";
import SmallBtn from "./SmallBtn.vue";
import MiniInput from "../MiniInput.vue";
import MiniSelect from "../MiniSelect.vue";

const props = defineProps<{ index: number; total: number; q: AssessmentQuestion; mode: "teacher" | "student" }>();
const emit = defineEmits<{
  "update:q": [q: AssessmentQuestion];
  move: [dir: -1 | 1];
  duplicate: [];
  delete: [];
  regenerate: [];
}>();

const openScheme = ref(false);

function patch(p: Partial<AssessmentQuestion>) {
  emit("update:q", { ...props.q, ...p });
}
</script>

<template>
  <div class="bg-bg-card border border-border rounded-[14px] p-4 md:p-5">
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-lg bg-violet-50 grid place-items-center shrink-0 text-[12.5px] font-bold text-violet-700">{{ index + 1 }}</div>
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap gap-1.5 mb-2">
          <MetaChip>{{ q.type }}</MetaChip>
          <MetaChip>{{ q.topic }}</MetaChip>
          <MetaChip>{{ q.difficulty }}</MetaChip>
          <MetaChip>{{ q.marks }} mark{{ q.marks === 1 ? "" : "s" }}</MetaChip>
        </div>
        <p v-if="mode === 'student'" class="text-[14px] text-ink leading-[1.55]">{{ q.text }}</p>
        <textarea
          v-else
          :value="q.text"
          class="w-full bg-bg-soft/50 border border-transparent hover:border-border focus:border-violet-300 focus:bg-bg-card rounded-lg px-3 py-2 text-[14px] text-ink leading-[1.55] outline-none min-h-[64px]"
          @input="patch({ text: ($event.target as HTMLTextAreaElement).value })"
        />
      </div>
      <div v-if="mode !== 'student'" class="flex flex-col gap-1 shrink-0">
        <IconBtn title="Move up" :disabled="index === 0" @click="emit('move', -1)"><ArrowUp :size="13" /></IconBtn>
        <IconBtn title="Move down" :disabled="index === total - 1" @click="emit('move', 1)"><ArrowDown :size="13" /></IconBtn>
      </div>
    </div>

    <template v-if="mode !== 'student'">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3">
        <MiniSelect
          label="Type"
          :model-value="q.type"
          :options="['Multiple Choice', 'Short Answer', 'Structured', 'Extended Response', 'Data Analysis']"
          @update:model-value="patch({ type: $event as AssessmentQuestion['type'] })"
        />
        <MiniSelect
          label="Difficulty"
          :model-value="q.difficulty"
          :options="['Easy', 'Medium', 'Hard']"
          @update:model-value="patch({ difficulty: $event as AssessmentQuestion['difficulty'] })"
        />
        <MiniInput label="Topic" :model-value="q.topic" @update:model-value="patch({ topic: $event })" />
        <MiniInput label="Marks" type="number" :model-value="String(q.marks)" @update:model-value="patch({ marks: Number($event) || 0 })" />
      </div>

      <div class="mt-3 flex flex-wrap gap-2 justify-between">
        <button class="inline-flex items-center gap-1 text-[12px] font-semibold text-violet-700 hover:text-violet-800" @click="openScheme = !openScheme">
          <ChevronDown :size="13" :class="`transition-transform ${openScheme ? 'rotate-180' : ''}`" />
          {{ openScheme ? "Hide" : "View" }} Mark Scheme
        </button>
        <div class="flex flex-wrap gap-1.5">
          <SmallBtn @click="emit('regenerate')"><RefreshCw :size="12" /> Regenerate</SmallBtn>
          <SmallBtn @click="emit('duplicate')"><Copy :size="12" /> Duplicate</SmallBtn>
          <SmallBtn danger @click="emit('delete')"><Trash2 :size="12" /> Remove</SmallBtn>
        </div>
      </div>

      <div v-if="openScheme" class="mt-3 bg-violet-50/60 border border-violet-100 rounded-lg p-3 space-y-2">
        <MiniInput label="Expected answer" :model-value="q.expectedAnswer" @update:model-value="patch({ expectedAnswer: $event })" />
        <div>
          <div class="text-[11.5px] font-semibold text-ink2 mb-1">Marking points</div>
          <textarea
            :value="q.markPoints.join('\n')"
            class="w-full bg-bg-card border border-border rounded-lg px-3 py-2 text-[13px] text-ink outline-none focus:border-violet-300 min-h-[70px]"
            @input="patch({ markPoints: ($event.target as HTMLTextAreaElement).value.split('\n').filter(Boolean) })"
          />
        </div>
        <MiniInput label="Examiner guidance" :model-value="q.guidance" @update:model-value="patch({ guidance: $event })" />
      </div>
    </template>
  </div>
</template>
