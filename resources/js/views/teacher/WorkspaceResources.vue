<script setup lang="ts">
import { ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import { Library, BookOpen, FileText, ClipboardList, GraduationCap, Compass } from "lucide-vue-next";
import { PageHeader, Section, EmptyState, FilterBar, FilterSelect, SearchInput } from "@/components/teacher/workspace-ui";

setPageMeta({ title: "Teaching Resources — Teacher Workspace" });

const CATEGORIES = [
  { icon: BookOpen, label: "Lesson Support", desc: "Explanations, examples and slides to support lessons." },
  { icon: Compass, label: "Revision Materials", desc: "Summaries and revision guides for exam preparation." },
  { icon: ClipboardList, label: "Practice Questions", desc: "Topic-based questions with worked solutions." },
  { icon: GraduationCap, label: "Exam Resources", desc: "Exam-board-aligned papers and mark schemes." },
  { icon: FileText, label: "Teaching Guides", desc: "Pedagogical guides and best-practice notes." },
];

const q = ref("");
const subject = ref("All subjects");
const qual = ref("All qualifications");
const board = ref("All boards");
</script>

<template>
  <PageHeader title="Teaching Resources" subtitle="Explore subject-specific resources aligned with your qualifications and exam boards." />
  <FilterBar>
    <SearchInput v-model="q" placeholder="Search resources…" />
    <FilterSelect v-model="subject" label="Subject" :options="['All subjects', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'English', 'History']" />
    <FilterSelect v-model="qual" label="Qualification" :options="['All qualifications', 'GCSE', 'IGCSE', 'AS Level', 'A Level']" />
    <FilterSelect v-model="board" label="Board" :options="['All boards', 'AQA', 'Edexcel', 'OCR', 'CIE', 'WJEC']" />
  </FilterBar>

  <Section title="Browse by category">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <button v-for="c in CATEGORIES" :key="c.label" class="text-left bg-bg-card border border-border rounded-[14px] p-4 hover:border-violet-300 hover:-translate-y-[1px] transition-all">
        <div class="w-10 h-10 rounded-xl bg-violet-50 grid place-items-center mb-3">
          <component :is="c.icon" :size="18" class="text-violet-600" />
        </div>
        <div class="text-[14px] font-semibold text-ink leading-tight">{{ c.label }}</div>
        <div class="text-[12.5px] text-ink3 font-light mt-1 leading-[1.5]">{{ c.desc }}</div>
      </button>
    </div>
  </Section>

  <Section title="Featured resources">
    <EmptyState
      :icon="Library"
      title="Curated resources are on the way"
      desc="AI Star Prep is building a library of teaching resources aligned with your qualifications and exam boards. Set your subjects during onboarding to receive tailored recommendations."
    />
  </Section>
</template>
