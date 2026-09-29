<script setup lang="ts">
import { reactive } from "vue";
import { toast } from "vue-sonner";
import { Plus } from "lucide-vue-next";
import { Field, TextInput, NumberInput, Select, FormCard } from "@/components/teacher/creation-ui";
import { readOnboarding, uid, type TeacherClass } from "@/lib/teacherStore";
import { useCatalogOptions } from "@/data/composables/useCatalogOptions";

const { qualifications: QUALS, boards: BOARDS, subjects: SUBJECTS } = useCatalogOptions();

const emit = defineEmits<{ close: []; save: [c: TeacherClass] }>();

const onboarding = readOnboarding();
const f = reactive({
  name: "",
  subject: onboarding.subjects?.[0] || "",
  qualification: onboarding.quals?.[0] || "GCSE",
  board: onboarding.boards?.[0] || "AQA",
  year: "",
  size: "",
});

function submit() {
  if (!f.name.trim()) {
    toast.error("Give the class a name.");
    return;
  }
  if (!f.subject) {
    toast.error("Select a subject.");
    return;
  }
  emit("save", {
    id: uid(),
    name: f.name.trim(),
    subject: f.subject,
    qualification: f.qualification || "GCSE",
    board: f.board || "AQA",
    year: f.year || "",
    size: f.size || "",
  });
}
</script>

<template>
  <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 grid place-items-center p-4">
    <div class="bg-bg-card border border-border rounded-2xl w-full max-w-[520px] p-5 shadow-[0_24px_60px_rgba(15,23,42,0.2)]">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div>
          <h3 class="text-[17px] font-bold text-ink tracking-[-0.01em]">Create a new class</h3>
          <p class="text-[12.5px] text-ink3 font-light mt-1">Organise students by group, subject and qualification.</p>
        </div>
        <button class="text-ink3 hover:text-ink text-xl leading-none" @click="emit('close')">×</button>
      </div>
      <FormCard title="Class details">
        <Field label="Class name">
          <TextInput v-model="f.name" placeholder="e.g. 10A Mathematics" />
        </Field>
        <div class="grid grid-cols-2 gap-3">
          <Field label="Subject">
            <Select v-model="f.subject" :options="SUBJECTS" placeholder="Select subject" />
          </Field>
          <Field label="Year group">
            <TextInput v-model="f.year" placeholder="e.g. Year 10" />
          </Field>
          <Field label="Qualification">
            <Select v-model="f.qualification" :options="QUALS" />
          </Field>
          <Field label="Exam board">
            <Select v-model="f.board" :options="BOARDS" />
          </Field>
          <Field label="Class size">
            <NumberInput v-model="f.size" min="0" />
          </Field>
        </div>
      </FormCard>
      <div class="flex justify-end gap-2 mt-5">
        <button class="text-[13px] font-semibold text-ink2 px-4 py-2 rounded-full hover:bg-bg-soft" @click="emit('close')">Cancel</button>
        <button class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2 rounded-full hover:bg-violet-700 transition" @click="submit">
          <Plus :size="14" /> Create Class
        </button>
      </div>
    </div>
  </div>
</template>
