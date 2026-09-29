<script setup lang="ts">
/**
 * Ported from AppShell.tsx. `children` -> default slot, `right` -> named
 * `#right` slot (presence of the slot, via useSlots, replaces the source's
 * `right ? ... : ...` branch on a possibly-undefined ReactNode prop).
 */
import { computed, onMounted, ref, useSlots } from "vue";
import { useRoute } from "vue-router";
import { useAuthSession } from "@/lib/authSession";
import { currentUser, defaultAuthenticatedPath } from "@/lib/api";
import { checkDashboardAccess, dashboardAccess } from "@/lib/subscriptionAccess";
import Logo from "@/components/brand/Logo.vue";
import DoodleBg from "@/components/decor/DoodleBg.vue";
import ProfileMenu from "@/components/app/ProfileMenu.vue";
import NotificationsDropdown from "@/components/app/NotificationsDropdown.vue";
import BackBar from "@/components/app/BackBar.vue";
import {
  LayoutDashboard, BookOpen, Timer, BarChart3, Gauge, Settings, Search, Menu, X, LogOut,
  HelpCircle, MessageSquare,
} from "lucide-vue-next";

interface NavItem { to: string; label: string; icon: unknown }
interface NavGroup { label?: string; items: NavItem[] }

const props = defineProps<{
  title: string;
  back?: { to?: string; label?: string };
}>();

const slots = useSlots();
const hasRight = computed(() => !!slots.right);

const studentNavGroups: NavGroup[] = [
  { items: [{ to: "/dashboard", label: "Dashboard", icon: LayoutDashboard }] },
  {
    label: "Learning",
    items: [
      { to: "/learn", label: "Learn Mode", icon: BookOpen },
      { to: "/exam", label: "Exam Mode", icon: Timer },
      { to: "/results", label: "Results", icon: BarChart3 },
      { to: "/readiness", label: "Readiness", icon: Gauge },
    ],
  },
  {
    label: "Support",
    items: [
      { to: "/help", label: "Help", icon: HelpCircle },
      { to: "/support", label: "Support", icon: MessageSquare },
    ],
  },
  {
    label: "Account",
    items: [{ to: "/settings", label: "Settings", icon: Settings }],
  },
];

const teacherNavGroups: NavGroup[] = [
  { items: [{ to: "/teacher/workspace", label: "Teacher workspace", icon: LayoutDashboard }] },
  { label: "Practice & review", items: [
    { to: "/learn", label: "Learn Mode", icon: BookOpen },
    { to: "/exam", label: "Exam Mode", icon: Timer },
    { to: "/results", label: "Student Results", icon: BarChart3 },
  ] },
  { label: "Support", items: [
    { to: "/help", label: "Help", icon: HelpCircle },
    { to: "/support", label: "Support", icon: MessageSquare },
  ] },
];
const tutorNavGroups: NavGroup[] = [
  { label: "Practice & review", items: [
    { to: "/learn", label: "Learn Mode", icon: BookOpen },
    { to: "/exam", label: "Exam Mode", icon: Timer },
    { to: "/results", label: "My Results", icon: BarChart3 },
    { to: "/tutor/review", label: "AI Feedback Review", icon: MessageSquare },
  ] },
  { label: "Support", items: [
    { to: "/help", label: "Help", icon: HelpCircle },
    { to: "/support", label: "Support", icon: MessageSquare },
  ] },
];
const user = computed(() => currentUser());
const isTeacher = computed(() => user.value?.roles?.some((role) => role.slug === "teacher") ?? false);
const isTutor = computed(() => user.value?.roles?.some((role) => role.slug === "tutor") ?? false);
const canOpenDashboard = computed(() => dashboardAccess.value === "allowed");
const visibleStudentNavGroups = computed(() => studentNavGroups
  .map((group) => ({ ...group, items: group.items.filter((item) => item.to !== "/dashboard" || canOpenDashboard.value) }))
  .filter((group) => group.items.length));
const navGroups = computed(() => isTeacher.value ? teacherNavGroups : isTutor.value ? tutorNavGroups : visibleStudentNavGroups.value);

// Mobile bottom nav — most-used 5
const studentBottomNav = [
  { to: "/dashboard", label: "Home", icon: LayoutDashboard },
  { to: "/learn", label: "Learn", icon: BookOpen },
  { to: "/exam", label: "Exam", icon: Timer },
  { to: "/results", label: "Results", icon: BarChart3 },
  { to: "/settings", label: "More", icon: Settings },
] as const;
const teacherBottomNav = [
  { to: "/teacher/workspace", label: "Workspace", icon: LayoutDashboard },
  { to: "/learn", label: "Learn", icon: BookOpen },
  { to: "/exam", label: "Exam", icon: Timer },
  { to: "/results", label: "Results", icon: BarChart3 },
] as const;
const tutorBottomNav = [
  { to: "/learn", label: "Learn", icon: BookOpen },
  { to: "/exam", label: "Exam", icon: Timer },
  { to: "/results", label: "Results", icon: BarChart3 },
  { to: "/tutor/review", label: "Feedback", icon: MessageSquare },
] as const;
const bottomNav = computed(() => isTeacher.value ? teacherBottomNav : isTutor.value ? tutorBottomNav : studentBottomNav.filter((item) => item.to !== "/dashboard" || canOpenDashboard.value));
const resolvedBack = computed(() => props.back ? { ...props.back, to: props.back.to === "/dashboard" && (isTeacher.value || isTutor.value) ? defaultAuthenticatedPath(user.value) : props.back.to } : undefined);

const open = ref(false);
const { signingOut, signOut } = useAuthSession();
const route = useRoute();
const pathname = computed(() => route.path);

onMounted(() => { if (!isTeacher.value && !isTutor.value) void checkDashboardAccess(); });

function isActive(to: string) {
  return pathname.value === to || pathname.value.startsWith(to + "/");
}
</script>

<template>
  <div class="h-dvh bg-bg flex relative overflow-hidden">
    <DoodleBg density="sparse" />

    <!-- Sidebar — desktop -->
    <aside class="hidden lg:flex w-[240px] shrink-0 flex-col border-r border-border bg-bg-card sticky top-0 h-dvh self-start">
      <div class="px-5 py-4 border-b border-border"><Logo /></div>
      <nav class="flex-1 px-3 py-4 flex flex-col gap-3 overflow-y-auto">
        <div v-for="(group, gi) in navGroups" :key="group.label ?? `g-${gi}`" class="flex flex-col gap-0.5">
          <div v-if="group.label" class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink4">
            {{ group.label }}
          </div>
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            :class="`flex items-center gap-3 px-3 py-2.5 rounded-md text-[14px] font-medium transition ${
              isActive(item.to) ? 'bg-blue-light text-blue' : 'text-ink2 hover:bg-bg-soft hover:text-ink'
            }`"
          >
            <component :is="item.icon" :size="16" :stroke-width="2" />
            {{ item.label }}
          </RouterLink>
        </div>
      </nav>
      <div class="px-3 py-3 border-t border-border">
        <button type="button" :disabled="signingOut" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-[13px] text-ink3 hover:bg-bg-soft hover:text-ink disabled:opacity-50" @click="signOut()">
          <LogOut :size="15" /> {{ signingOut ? "Signing out…" : "Sign out" }}
        </button>
      </div>
    </aside>

    <!-- Mobile drawer -->
    <div v-if="open" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-ink/30" @click="open = false" />
      <aside class="absolute left-0 top-0 bottom-0 w-[280px] bg-bg-card flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border">
          <Logo />
          <button aria-label="Close menu" class="text-ink2 min-w-11 min-h-11 grid place-items-center" @click="open = false"><X :size="20" /></button>
        </div>
        <nav class="flex-1 px-3 py-4 flex flex-col gap-3 overflow-y-auto">
          <div v-for="(group, gi) in navGroups" :key="group.label ?? `mg-${gi}`" class="flex flex-col gap-0.5">
            <div v-if="group.label" class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink4">
              {{ group.label }}
            </div>
            <RouterLink
              v-for="item in group.items"
              :key="item.to"
              :to="item.to"
              :class="`flex items-center gap-3 px-3 py-3 rounded-md text-[14px] font-medium min-h-11 ${isActive(item.to) ? 'bg-blue-light text-blue' : 'text-ink2'}`"
              @click="open = false"
            >
              <component :is="item.icon" :size="18" /> {{ item.label }}
            </RouterLink>
          </div>
        </nav>
        <div class="px-3 py-3 border-t border-border">
          <button type="button" :disabled="signingOut" class="w-full flex items-center gap-3 px-3 py-3 rounded-md text-[14px] text-ink2 disabled:opacity-50" @click="signOut()">
            <LogOut :size="18" /> {{ signingOut ? "Signing out…" : "Sign out" }}
          </button>
        </div>
      </aside>
    </div>

    <!-- Main -->
    <div class="flex-1 min-w-0 flex flex-col overflow-y-auto">
      <header class="sticky top-0 z-30 border-b border-border bg-bg/85 backdrop-blur">
        <div class="flex items-center gap-3 px-4 lg:px-8 h-14 lg:h-16 max-w-[1280px] mx-auto w-full">
          <button aria-label="Open menu" class="lg:hidden text-ink2 min-w-11 min-h-11 grid place-items-center -ml-2" @click="open = true"><Menu :size="20" /></button>
          <h1 class="text-[18px] lg:text-[20px] font-bold text-ink tracking-[-0.02em] flex-1 truncate">{{ title }}</h1>
          <div class="hidden md:flex items-center gap-2 bg-bg-soft border border-border rounded-md px-3 py-1.5 w-[260px]">
            <Search :size="14" class="text-ink4" />
            <input placeholder="Search topics, sessions…" aria-label="Search" class="bg-transparent text-[13px] text-ink2 placeholder:text-ink4 outline-none w-full" />
          </div>
          <NotificationsDropdown v-if="!isTeacher && !isTutor" />
          <ProfileMenu />
        </div>
      </header>
      <main class="flex-1 px-4 lg:px-8 py-6 lg:py-8 pb-24 lg:pb-8 max-w-[1280px] w-full mx-auto">
        <BackBar v-if="resolvedBack" :to="resolvedBack.to" :label="resolvedBack.label" />
        <div v-if="hasRight" class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">
          <div class="min-w-0"><slot /></div>
          <div class="space-y-5"><slot name="right" /></div>
        </div>
        <slot v-else />
      </main>
    </div>

    <!-- Mobile bottom nav -->
    <nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden bg-bg-card/95 backdrop-blur border-t border-border" aria-label="Primary">
      <div class="grid" :style="{ gridTemplateColumns: `repeat(${bottomNav.length}, minmax(0, 1fr))` }">
        <RouterLink
          v-for="item in bottomNav"
          :key="item.to"
          :to="item.to"
          :class="`flex flex-col items-center justify-center gap-1 py-2.5 min-h-[58px] text-[11px] font-medium ${isActive(item.to) ? 'text-blue' : 'text-ink3'}`"
        >
          <component :is="item.icon" :size="20" :stroke-width="isActive(item.to) ? 2.4 : 2" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </div>
    </nav>
  </div>
</template>
