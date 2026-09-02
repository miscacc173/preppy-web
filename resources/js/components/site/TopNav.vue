<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import Logo from "@/components/brand/Logo.vue";
import { Menu, X } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { currentUser, defaultAuthenticatedPath } from "@/lib/api";
import { checkDashboardAccess, dashboardAccess } from "@/lib/subscriptionAccess";
import ProfileMenu from "@/components/app/ProfileMenu.vue";

type NavItem = { to: string; label: string; hash?: string };

const PUBLIC_NAV_ITEMS: NavItem[] = [
  { to: "/", label: "Home" },
  { to: "/features", label: "Features" },
  { to: "/pricing", label: "Pricing" },
  { to: "/", hash: "testimonials", label: "Testimonials" },
  { to: "/faq", label: "FAQ" },
  { to: "/latest-news", label: "Latest News" },
  { to: "/contact", label: "Contact" },
];
const user = computed(() => currentUser());
const usesStudentWorkspace = computed(() => {
  const roles = user.value?.roles ?? [];
  return roles.some((role) => role.slug === "student")
    && !roles.some((role) => ["system_admin", "system_staff", "institute_admin", "institute_staff", "teacher", "tutor", "parent"].includes(role.slug));
});
const canOpenDashboard = computed(() => dashboardAccess.value === "allowed");
const workspaceLabel = computed(() => {
  const roles = user.value?.roles ?? [];
  if (roles.some((role) => ["system_admin", "system_staff"].includes(role.slug))) return "Administration";
  if (roles.some((role) => ["institute_admin", "institute_staff"].includes(role.slug))) return "Institute";
  if (roles.some((role) => role.slug === "teacher")) return "Teacher workspace";
  if (roles.some((role) => role.slug === "tutor")) return "Tutor review";
  if (roles.some((role) => role.slug === "parent")) return "Parent portal";
  return canOpenDashboard.value ? "Dashboard" : "Activate plan";
});
const workspacePath = computed(() => usesStudentWorkspace.value && !canOpenDashboard.value ? "/pricing" : defaultAuthenticatedPath(user.value));
const NAV_ITEMS = computed<NavItem[]>(() => user.value ? [PUBLIC_NAV_ITEMS[0], { to: workspacePath.value, label: workspaceLabel.value }, ...PUBLIC_NAV_ITEMS.slice(1)] : PUBLIC_NAV_ITEMS);

const route = useRoute();

function isActive(item: NavItem) {
  if (item.hash) return route.path === item.to && route.hash === `#${item.hash}`;
  return route.path === item.to;
}

function linkTarget(item: NavItem) {
  return item.hash ? { path: item.to, hash: `#${item.hash}` } : item.to;
}

const open = ref(false);
const navRef = ref<HTMLElement | null>(null);

onMounted(() => { if (usesStudentWorkspace.value) void checkDashboardAccess(); });

function handleClick(e: MouseEvent) {
  if (navRef.value && !navRef.value.contains(e.target as Node)) open.value = false;
}
function handleKey(e: KeyboardEvent) {
  if (e.key === "Escape") open.value = false;
}

watch(open, (isOpen) => {
  if (isOpen) {
    document.addEventListener("mousedown", handleClick);
    document.addEventListener("keydown", handleKey);
    document.body.style.overflow = "hidden";
  } else {
    document.removeEventListener("mousedown", handleClick);
    document.removeEventListener("keydown", handleKey);
    document.body.style.overflow = "";
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", handleClick);
  document.removeEventListener("keydown", handleKey);
  document.body.style.overflow = "";
});
</script>

<template>
  <nav ref="navRef" class="sticky top-0 z-50 border-b border-border bg-bg/85 backdrop-blur-xl">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
      <div class="flex h-16 items-center justify-between gap-4">
        <Logo />

        <!-- Desktop links -->
        <div class="hidden lg:flex items-center gap-1">
          <RouterLink
            v-for="it in NAV_ITEMS"
            :key="it.label"
            :to="linkTarget(it)"
            :aria-current="isActive(it) ? 'page' : undefined"
            :class="
              cn(
                'group relative inline-flex items-center rounded-full font-medium transition-colors duration-250 justify-center text-[13px] px-3 py-2',
                isActive(it) ? 'text-blue font-semibold' : 'text-ink3 hover:text-ink',
              )
            "
          >
            <span
              :class="
                cn(
                  'absolute inset-0 rounded-full bg-blue-light/60 -z-10 transition-all duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]',
                  isActive(it) ? 'opacity-100 scale-100' : 'opacity-0 scale-90 group-hover:opacity-100 group-hover:scale-100',
                )
              "
            />
            <span class="relative z-10">{{ it.label }}</span>
          </RouterLink>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 shrink-0">
          <RouterLink
            v-if="!user"
            to="/login"
            class="hidden md:inline-flex items-center justify-center text-[13px] font-medium text-ink2 px-4 py-2 rounded-full border border-border hover:bg-bg-soft hover:text-ink transition-colors"
          >
            Sign in
          </RouterLink>
          <RouterLink
            v-if="!user"
            to="/demo"
            class="hidden md:inline-flex items-center justify-center text-[13px] font-semibold text-white bg-blue px-4 py-2 rounded-full hover:bg-blue/90 hover:shadow-[0_8px_24px_-6px_rgba(59,110,248,0.35)] transition-all"
          >
            Try demo free
          </RouterLink>
          <ProfileMenu v-if="user" />
          <button
            class="lg:hidden p-2.5 text-ink rounded-full hover:bg-bg-soft transition-colors"
            :aria-label="open ? 'Close menu' : 'Open menu'"
            :aria-expanded="open"
            @click="open = !open"
          >
            <X v-if="open" :size="20" />
            <Menu v-else :size="20" />
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Mobile menu overlay -->
  <div v-if="open" class="lg:hidden fixed inset-0 z-40 bg-ink/20 backdrop-blur-sm" />

  <!-- Mobile drawer -->
  <div
    :class="
      cn(
        'lg:hidden fixed top-0 right-0 z-50 h-full w-[min(320px,85vw)] bg-bg-card border-l border-border shadow-2xl',
        'transform transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]',
        open ? 'translate-x-0 pointer-events-auto' : 'translate-x-full pointer-events-none',
      )
    "
  >
    <div class="flex items-center justify-between px-5 h-16 border-b border-border">
      <span class="font-display text-[17px] font-bold text-ink">Menu</span>
      <button class="p-2 text-ink rounded-full hover:bg-bg-soft transition-colors" aria-label="Close menu" @click="open = false">
        <X :size="20" />
      </button>
    </div>
    <div class="flex flex-col p-4 gap-1">
      <RouterLink
        v-for="it in NAV_ITEMS"
        :key="it.label"
        :to="linkTarget(it)"
        :aria-current="isActive(it) ? 'page' : undefined"
        :class="
          cn(
            'group relative inline-flex items-center rounded-full font-medium transition-colors duration-250 justify-start text-[15px] px-4 py-3',
            isActive(it) ? 'text-blue font-semibold' : 'text-ink3 hover:text-ink',
          )
        "
        @click="open = false"
      >
        <span
          :class="
            cn(
              'absolute inset-0 rounded-full bg-blue-light/60 -z-10 transition-all duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]',
              isActive(it) ? 'opacity-100 scale-100' : 'opacity-0 scale-90 group-hover:opacity-100 group-hover:scale-100',
            )
          "
        />
        <span class="relative z-10">{{ it.label }}</span>
      </RouterLink>
    </div>
    <div class="absolute bottom-0 left-0 right-0 p-5 border-t border-border bg-bg-card flex flex-col gap-3">
      <RouterLink
        v-if="!user"
        to="/login"
        class="w-full text-center text-[14px] font-medium text-ink2 bg-bg-card border border-border py-2.5 rounded-full hover:bg-bg-soft transition-colors"
        @click="open = false"
      >
        Sign in
      </RouterLink>
      <RouterLink
        v-if="!user"
        to="/demo"
        class="w-full text-center text-[14px] font-semibold text-white bg-blue py-2.5 rounded-full hover:bg-blue/90 transition-colors"
        @click="open = false"
      >
        Try demo free
      </RouterLink>
    </div>
  </div>
</template>
