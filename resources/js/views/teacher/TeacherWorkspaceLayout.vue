<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import {
  Plus, ClipboardList, Wand2, Target, LayoutDashboard, LineChart, School, Library, BookMarked, ChevronRight, UsersRound,
} from "lucide-vue-next";

setPageMeta({
  title: "Teacher Workspace — Preppy",
  description: "Your AI Star Prep teaching workspace: manage classes, create assessments and activities, and follow student progress.",
});

interface Onboarding {
  displayName?: string;
  school?: string;
  role?: string;
}

const NAV = [
  { to: "/teacher/workspace", label: "Overview", icon: LayoutDashboard, exact: true },
  { to: "/teacher/workspace/classes", label: "Classes", icon: School },
  { to: "/teacher/workspace/assessments", label: "Assessments", icon: ClipboardList },
  { to: "/teacher/workspace/activities", label: "Learning Activities", icon: Wand2 },
  { to: "/teacher/workspace/insights", label: "Student Insights", icon: LineChart },
  { to: "/teacher/workspace/parents", label: "Parent Access", icon: UsersRound },
  { to: "/teacher/workspace/resources", label: "Resources", icon: Library },
  { to: "/teacher/workspace/development", label: "My Development", icon: BookMarked },
];

const data = ref<Onboarding | null>(null);
const createOpen = ref(false);
const createRef = ref<HTMLDivElement | null>(null);
const route = useRoute();
const pathname = computed(() => route.path);

onMounted(() => {
  try {
    const raw = localStorage.getItem("aistar:teacherOnboarding");
    if (raw) data.value = JSON.parse(raw);
  } catch {
    /* ignore */
  }
});

function onDoc(e: MouseEvent) {
  if (createRef.value && !createRef.value.contains(e.target as Node)) createOpen.value = false;
}
onMounted(() => document.addEventListener("mousedown", onDoc));
onBeforeUnmount(() => document.removeEventListener("mousedown", onDoc));

const teacher = computed(() => data.value?.displayName?.trim() || "Teacher");

function isActive(n: (typeof NAV)[number]) {
  return n.exact ? pathname.value === n.to || pathname.value === n.to + "/" : pathname.value === n.to || pathname.value.startsWith(n.to + "/");
}

const CREATE_ITEMS = [
  { icon: ClipboardList, label: "Create Assessment", desc: "Exam-board aligned test", to: "/teacher/workspace/assessments/new" },
  { icon: Wand2, label: "Create Learning Activity", desc: "Guided lesson or revision", to: "/teacher/workspace/activities/new" },
  { icon: Target, label: "Assign Practice", desc: "Focused topic work", to: "/teacher/workspace/practice/new" },
  { icon: School, label: "Create Class", desc: "Organise students by group", to: "/teacher/workspace/classes" },
];
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <TopNav />

    <main class="flex-1">
      <!-- Consistent workspace header -->
      <section class="px-6 pt-10 pb-6 border-b border-border bg-[#FAFBFC]">
        <div class="max-w-[1200px] mx-auto flex flex-col md:flex-row md:items-end md:justify-between gap-5">
          <div>
            <p class="label-mono mb-2 text-violet-600">Teacher workspace</p>
            <h1 class="text-[26px] md:text-[32px] font-bold text-ink tracking-[-0.02em] leading-tight">
              Welcome back, <span class="text-violet-600">{{ teacher }}</span>
            </h1>
            <p class="text-[14px] text-ink2 leading-[1.6] font-light mt-1.5 max-w-[620px]">
              Manage your classes, create learning activities and understand where students need support.
            </p>
          </div>

          <div ref="createRef" class="relative">
            <button
              class="inline-flex items-center gap-2 bg-violet-600 text-white text-[14px] font-semibold px-5 py-2.5 rounded-full hover:bg-violet-700 hover:shadow-[0_8px_24px_-6px_rgba(124,58,237,0.35)] transition-all"
              @click="createOpen = !createOpen"
            >
              <Plus :size="16" /> Create
            </button>
            <div v-if="createOpen" class="absolute right-0 mt-2 w-[260px] bg-bg-card border border-border rounded-xl shadow-[0_16px_40px_rgba(15,23,42,0.12)] p-1.5 z-20">
              <RouterLink
                v-for="item in CREATE_ITEMS"
                :key="item.label"
                :to="item.to"
                class="w-full flex items-start gap-2.5 px-2.5 py-2 rounded-lg hover:bg-violet-50 text-left transition"
                @click="createOpen = false"
              >
                <div class="w-8 h-8 rounded-lg bg-violet-50 grid place-items-center shrink-0">
                  <component :is="item.icon" :size="15" class="text-violet-600" />
                </div>
                <div>
                  <div class="text-[13px] font-semibold text-ink leading-tight">{{ item.label }}</div>
                  <div class="text-[11.5px] text-ink3 font-light mt-0.5">{{ item.desc }}</div>
                </div>
              </RouterLink>
            </div>
          </div>
        </div>
      </section>

      <div class="max-w-[1200px] mx-auto px-5 md:px-6 py-8 md:py-10 grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-8">
        <!-- Sidebar -->
        <aside class="lg:sticky lg:top-6 self-start">
          <nav class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
            <RouterLink
              v-for="n in NAV"
              :key="n.to"
              :to="n.to"
              :class="`flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13.5px] font-medium whitespace-nowrap transition ${
                isActive(n) ? 'bg-violet-50 text-violet-700' : 'text-ink2 hover:bg-bg-soft hover:text-ink'
              }`"
            >
              <component :is="n.icon" :size="16" :class="isActive(n) ? 'text-violet-600' : 'text-ink3'" />
              {{ n.label }}
            </RouterLink>
          </nav>
        </aside>

        <!-- Content -->
        <div class="space-y-8 min-w-0">
          <RouterView />

          <div class="pt-2">
            <RouterLink to="/teacher" class="text-[13px] font-medium text-ink3 hover:text-violet-600 inline-flex items-center gap-1">
              <ChevronRight :size="14" class="rotate-180" /> Update teacher setup
            </RouterLink>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
