<script setup lang="ts">
import { setPageMeta } from "@/lib/useRouteHead";
import { BookMarked, Compass, GraduationCap, Library, Award, ClipboardList, Wand2, Users, BookOpen } from "lucide-vue-next";
import { PageHeader, Section, EmptyState } from "@/components/teacher/workspace-ui";

setPageMeta({ title: "My Development — Teacher Workspace" });

const LEARNING = [
  { icon: Compass, label: "Explore difficult topics", desc: "Deep dives on topics students commonly struggle with." },
  { icon: BookMarked, label: "Review subject knowledge", desc: "Refresh key concepts across your subjects." },
  { icon: GraduationCap, label: "Try exam-style questions", desc: "Practise questions from real exam styles." },
  { icon: Library, label: "Continue previous learning", desc: "Pick up where you left off." },
];

const IMPACT = [
  { title: "First Assessment Created", desc: "Publish your first assessment to a class." },
  { title: "First Learning Activity Published", desc: "Share your first activity with students." },
  { title: "Consistent Weekly Teaching", desc: "Four weeks of steady class activity." },
  { title: "Student Improvement Milestone", desc: "A student improves by a full grade band." },
];

const ACTIVITY = [
  { icon: ClipboardList, label: "Assessments created", value: 0 },
  { icon: Wand2, label: "Learning activities published", value: 0 },
  { icon: Users, label: "Student responses reviewed", value: 0 },
  { icon: BookOpen, label: "Subjects supported", value: 0 },
];
</script>

<template>
  <PageHeader title="My Development" subtitle="Continue building your subject knowledge and track your teaching activity and professional milestones." />

  <Section title="Continue My Learning" subtitle="Keep your own subject knowledge sharp.">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <button v-for="l in LEARNING" :key="l.label" class="text-left bg-bg-card border border-border rounded-[14px] p-4 hover:border-violet-300 hover:-translate-y-[1px] transition-all">
        <div class="w-10 h-10 rounded-xl bg-violet-50 grid place-items-center mb-3">
          <component :is="l.icon" :size="18" class="text-violet-600" />
        </div>
        <div class="text-[13.5px] font-semibold text-ink leading-tight">{{ l.label }}</div>
        <div class="text-[12px] text-ink3 font-light mt-1 leading-[1.5]">{{ l.desc }}</div>
      </button>
    </div>
  </Section>

  <Section title="Teaching Impact" subtitle="Meaningful milestones from your teaching work.">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div v-for="m in IMPACT" :key="m.title" class="bg-bg-card border border-border rounded-[14px] p-4 flex items-start gap-3 opacity-80">
        <div class="w-10 h-10 rounded-xl bg-bg-soft grid place-items-center shrink-0">
          <Award :size="18" class="text-ink3" />
        </div>
        <div class="min-w-0">
          <div class="text-[13.5px] font-semibold text-ink leading-tight">{{ m.title }}</div>
          <div class="text-[12px] text-ink3 font-light mt-0.5 leading-[1.5]">{{ m.desc }}</div>
        </div>
      </div>
    </div>
  </Section>

  <Section title="My Activity" subtitle="Your teaching activity at a glance.">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
      <div v-for="a in ACTIVITY" :key="a.label" class="bg-bg-card border border-border rounded-[14px] p-4">
        <div class="flex items-center gap-2.5 mb-2">
          <div class="w-8 h-8 rounded-lg bg-violet-50 grid place-items-center">
            <component :is="a.icon" :size="15" class="text-violet-600" />
          </div>
          <p class="text-[11.5px] font-semibold uppercase tracking-wider text-ink3">{{ a.label }}</p>
        </div>
        <div class="text-[22px] font-bold text-ink tracking-[-0.02em]">{{ a.value }}</div>
      </div>
    </div>
    <EmptyState
      :icon="BookMarked"
      title="Your activity summary will appear here"
      desc="As you create assessments, publish activities and review student work, this section fills in automatically."
    />
  </Section>
</template>
