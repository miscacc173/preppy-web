<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import {
  Plus, ClipboardList, Wand2, Target, TrendingUp, Users, School, Sparkles, AlertCircle,
  Library, BookMarked, LineChart, Compass, GraduationCap, Lightbulb,
} from "lucide-vue-next";
import { Card, Section, EmptyState } from "@/components/teacher/workspace-ui";
import { listClasses, listAssessments, listActivities, listPractice, readOnboarding, type TeacherClass } from "@/lib/teacherStore";
import StatCard from "./workspace-overview/StatCard.vue";
import ActionCard from "./workspace-overview/ActionCard.vue";
import MiniAction from "./workspace-overview/MiniAction.vue";
import InsightCard from "./workspace-overview/InsightCard.vue";
import ClassCard from "./workspace-overview/ClassCard.vue";
import Achievement from "./workspace-overview/Achievement.vue";
import RecommendedStart from "./workspace-overview/RecommendedStart.vue";

const router = useRouter();
const classes = ref<TeacherClass[]>([]);
const counts = ref({ assigned: 0, drafts: 0, practice: 0, activities: 0 });
const primarySubject = ref<string | undefined>();

onMounted(() => {
  classes.value = listClasses();
  const assess = listAssessments();
  const acts = listActivities();
  counts.value = {
    assigned: assess.filter((a) => a.status === "assigned").length,
    drafts: assess.filter((a) => a.status === "draft").length + acts.filter((a) => a.status === "draft").length,
    practice: listPractice().length,
    activities: acts.filter((a) => a.status === "assigned").length,
  };
  primarySubject.value = readOnboarding().subjects?.[0];
});

const totalStudents = computed(() => classes.value.reduce((n, c) => n + (Number(c.size) || 0), 0));

const recommendedTitle = computed(() => {
  if (classes.value.length === 0) return "Create your first class to start assigning practice and tracking readiness.";
  if (primarySubject.value) return `Create a short ${primarySubject.value} assessment to identify weak areas before your next lesson.`;
  return "Create an assessment to identify weak areas before your next lesson.";
});
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <StatCard label="Active Classes" :value="classes.length" :icon="School" tone="violet" />
    <StatCard label="Total Students" :value="totalStudents" :icon="Users" tone="blue" />
    <StatCard label="Assessments Assigned" :value="counts.assigned" :icon="ClipboardList" tone="emerald" />
    <StatCard label="Drafts to Finish" :value="counts.drafts" :icon="AlertCircle" tone="amber" />
  </div>

  <Card>
    <div class="flex flex-col md:flex-row md:items-center gap-4">
      <div class="w-11 h-11 rounded-xl bg-violet-50 grid place-items-center shrink-0">
        <Lightbulb :size="20" class="text-violet-600" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="label-mono text-violet-600 mb-1">Recommended next step</p>
        <h3 class="text-[16px] font-bold text-ink tracking-[-0.01em]">{{ recommendedTitle }}</h3>
      </div>
      <RecommendedStart :has-class="classes.length > 0" :subject="primarySubject" />
    </div>
  </Card>

  <Section title="What would you like to do?">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <ActionCard to="/teacher/workspace/assessments/new" :icon="ClipboardList" title="Create a Test" desc="Build an exam-board-aligned assessment for a class or student group." tone="violet" />
      <ActionCard to="/teacher/workspace/activities/new" :icon="Wand2" title="Create a Learning Activity" desc="Prepare guided learning content, practice or revision activities." tone="violet" />
      <ActionCard to="/teacher/workspace/practice/new" :icon="Target" title="Assign Practice" desc="Select subject content and assign focused practice to students." tone="blue" />
      <ActionCard to="/teacher/workspace/insights" :icon="TrendingUp" title="View Student Progress" desc="Review performance, engagement and areas needing attention." tone="emerald" />
    </div>
  </Section>

  <Section title="My Classes">
    <template #action>
      <RouterLink to="/teacher/workspace/classes" class="text-[13px] font-semibold text-violet-600 hover:text-violet-700 inline-flex items-center gap-1">
        {{ classes.length ? "Manage classes" : "New class" }} <Plus :size="13" />
      </RouterLink>
    </template>
    <EmptyState
      v-if="classes.length === 0"
      :icon="School"
      title="No classes yet"
      desc="Create your first class to begin assigning learning activities and tracking student progress."
      cta="Create First Class"
      @cta="router.push('/teacher/workspace/classes')"
    />
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <ClassCard v-for="c in classes.slice(0, 4)" :key="c.id" :c="c" />
    </div>
  </Section>

  <Section title="Student Insights">
    <EmptyState
      v-if="classes.length === 0 || counts.assigned + counts.practice === 0"
      :icon="LineChart"
      title="Insights will appear once students start practising"
      desc="After you create a class and assign activities, AI Star Prep will highlight common weak topics and students who may need support."
    />
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <InsightCard tone="amber" :icon="AlertCircle" title="Students needing attention" body="No submissions yet — insights appear after the first student response." />
      <InsightCard tone="violet" :icon="Sparkles" title="Common weak topics" body="AI will surface recurring weak areas across the class." />
      <InsightCard tone="emerald" :icon="TrendingUp" title="Class readiness" body="A rolling readiness score updates as students practise." />
    </div>
  </Section>

  <Section title="My Subject Development" subtitle="Keep your own subject knowledge sharp.">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <MiniAction to="/teacher/workspace/development" :icon="BookMarked" label="Continue My Learning" />
      <MiniAction to="/teacher/workspace/resources" :icon="Library" label="Explore Resources" />
      <MiniAction to="/teacher/workspace/development" :icon="Compass" label="Review Difficult Topics" />
      <MiniAction to="/teacher/workspace/development" :icon="GraduationCap" label="Exam-Style Questions" />
    </div>
  </Section>

  <Section title="Teaching Impact">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <Achievement :unlocked="counts.assigned >= 1" title="First Assessment Created" desc="Publish your first assessment to a class." />
      <Achievement title="100 Student Responses Reviewed" desc="Milestone for consistent feedback." />
      <Achievement :unlocked="counts.activities >= 10" title="10 Learning Activities Published" desc="Build a reusable library over time." />
      <Achievement title="Consistent Weekly Teaching" desc="Four weeks of steady class activity." />
      <Achievement title="Student Improvement Milestone" desc="A student improves by a full grade band." />
      <Achievement title="Class Readiness 80%+" desc="Take a class to strong exam readiness." />
    </div>
  </Section>
</template>
