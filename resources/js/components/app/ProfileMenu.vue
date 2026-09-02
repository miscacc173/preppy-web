<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import {
  User, CreditCard, BookOpen, Timer,
  Moon, Sun, Globe,
  LogOut, ChevronRight, Bell, Building2, ShieldCheck, GraduationCap, Users,
} from "lucide-vue-next";
import { useTheme } from "@/lib/theme";
import { currentUser } from "@/lib/api";
import { useAuthSession } from "@/lib/authSession";

interface Item {
  icon: unknown;
  label: string;
  to: string;
  search?: Record<string, string>;
  hint?: string;
}

const open = ref(false);
const { theme, toggle: toggleTheme } = useTheme();
const dark = computed(() => theme.value === "dark");
const { signingOut, signOut: endSession } = useAuthSession();
const user = computed(() => currentUser());
const initials = computed(() => (user.value?.name || "AS").split(/\s+/).map((part) => part[0]).join("").slice(0, 2).toUpperCase());
const roleLabel = computed(() => (user.value?.roles?.[0]?.slug || "account").replaceAll("_", " "));

async function signOut() {
  open.value = false;
  await endSession();
}

const rootEl = ref<HTMLDivElement | null>(null);

function onDoc(e: MouseEvent) {
  if (!rootEl.value?.contains(e.target as Node)) open.value = false;
}
onMounted(() => document.addEventListener("mousedown", onDoc));
onBeforeUnmount(() => document.removeEventListener("mousedown", onDoc));

const quick = computed<Item[]>(() => {
  const roles = user.value?.roles ?? [];
  const isStudent = roles.some((role) => role.slug === "student");
  const items: Item[] = isStudent ? [
    { icon: User, label: "My Profile", to: "/settings" },
    { icon: Bell, label: "Notifications", to: "/notifications" },
    { icon: CreditCard, label: "Billing & Subscription", to: "/billing" },
  ] : [];
  if (roles.some((role) => ["system_admin", "system_staff"].includes(role.slug))) items.unshift({ icon: ShieldCheck, label: "Administration", to: "/admin" });
  const instituteRole = roles.find((role) => ["institute_admin", "institute_staff"].includes(role.slug) && role.pivot?.context_id);
  if (instituteRole?.pivot?.context_id) items.unshift({ icon: Building2, label: "Institute workspace", to: `/institute/${instituteRole.pivot.context_id}` });
  if (roles.some((role) => role.slug === "teacher")) items.unshift({ icon: GraduationCap, label: "Teacher workspace", to: "/teacher/workspace" });
  if (roles.some((role) => role.slug === "tutor")) items.unshift(
    { icon: BookOpen, label: "Learn Mode", to: "/learn" },
    { icon: Timer, label: "Exam Mode", to: "/exam" },
    { icon: ShieldCheck, label: "AI content review", to: "/tutor/review" },
  );
  if (roles.some((role) => role.slug === "parent")) items.unshift({ icon: Users, label: "Parent portal", to: "/parent" });
  return items;
});
</script>

<template>
  <div ref="rootEl" class="relative">
    <button
      aria-label="Open account menu"
      :aria-expanded="open"
      class="grid place-items-center w-9 h-9 rounded-full bg-blue text-white text-xs font-semibold ring-2 ring-transparent hover:ring-blue-mid transition"
      @click="open = !open"
    >
      {{ initials }}
    </button>

    <div
      v-if="open"
      class="absolute right-0 top-[calc(100%+10px)] z-50 w-[340px] bg-bg-card border border-border rounded-[20px] shadow-[0_24px_60px_-20px_rgba(14,19,48,0.25),0_8px_20px_-12px_rgba(14,19,48,0.10)] overflow-hidden"
      style="animation: fade-in-up .22s cubic-bezier(.2,.8,.2,1) both"
    >
      <!-- Header -->
      <div class="p-4 bg-gradient-to-br from-blue-light to-violet-light/40 border-b border-border">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-blue text-white grid place-items-center font-semibold text-sm shrink-0">{{ initials }}</div>
          <div class="min-w-0 flex-1">
            <p class="text-[14px] font-semibold text-ink truncate">{{ user?.name || "AiStar Student" }}</p>
            <p class="text-[12px] text-ink3 truncate">{{ user?.email || "" }}</p>
          </div>
        </div>
        <div class="mt-3"><span class="inline-flex rounded-full border border-border bg-bg-card px-2.5 py-1 text-[10px] font-semibold capitalize text-ink2">{{ roleLabel }}</span></div>
      </div>

      <!-- Quick actions -->
      <div class="max-h-[260px] overflow-y-auto py-1.5">
        <RouterLink
          v-for="it in quick"
          :key="it.label"
          :to="{ path: it.to, query: it.search }"
          class="flex items-center gap-3 px-3 py-2.5 mx-1.5 rounded-md text-[13px] text-ink2 hover:bg-bg-soft hover:text-ink transition group"
          @click="open = false"
        >
          <span class="w-7 h-7 rounded-md grid place-items-center bg-bg-soft text-ink3 group-hover:bg-blue-light group-hover:text-blue transition">
            <component :is="it.icon" :size="14" />
          </span>
          <span class="flex-1">{{ it.label }}</span>
          <span v-if="it.hint" class="text-[10px] font-mono font-semibold text-gold bg-gold-light border border-[#F2DDA6] rounded-full px-1.5 py-0.5">
            {{ it.hint }}
          </span>
          <ChevronRight :size="13" class="text-ink4 opacity-0 group-hover:opacity-100 transition" />
        </RouterLink>
      </div>

      <!-- Preferences -->
      <div class="border-t border-border py-1.5">
        <!-- Dark mode toggle -->
        <div class="flex items-center gap-3 px-3 py-2.5 mx-1.5 rounded-md text-[13px] text-ink2">
          <span class="w-7 h-7 rounded-md grid place-items-center bg-bg-soft text-ink3">
            <Moon v-if="dark" :size="14" />
            <Sun v-else :size="14" />
          </span>
          <span class="flex-1">Dark Mode</span>
          <button
            role="switch"
            :aria-checked="dark"
            :class="`relative w-9 h-5 rounded-full transition ${dark ? 'bg-blue' : 'bg-bg-soft2 border border-border'}`"
            @click="toggleTheme"
          >
            <span :class="`absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform ${dark ? 'translate-x-4' : ''}`" />
          </button>
        </div>

        <!-- Language — links to dedicated page -->
        <RouterLink
          to="/language"
          class="w-full flex items-center gap-3 px-3 py-2.5 mx-1.5 rounded-md text-[13px] text-ink2 hover:bg-bg-soft hover:text-ink transition"
          style="width: calc(100% - 12px)"
          @click="open = false"
        >
          <span class="w-7 h-7 rounded-md grid place-items-center bg-bg-soft text-ink3"><Globe :size="14" /></span>
          <span class="flex-1 text-left">Language & Region</span>
          <ChevronRight :size="13" class="text-ink4" />
        </RouterLink>
      </div>

      <!-- Danger -->
      <div class="border-t border-border p-1.5">
        <button
          type="button"
          :disabled="signingOut"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-[13px] text-pink hover:bg-pink-light transition"
          @click="signOut"
        >
          <span class="w-7 h-7 rounded-md grid place-items-center bg-pink-light text-pink"><LogOut :size="14" /></span>
          <span class="flex-1">{{ signingOut ? "Signing out…" : "Sign Out" }}</span>
        </button>
      </div>
    </div>

  </div>
</template>
