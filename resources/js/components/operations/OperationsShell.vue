<script setup lang="ts">
import { computed, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { Check, ChevronLeft, LogOut, Menu, Moon, Sun, X } from "lucide-vue-next";
import Logo from "@/components/brand/Logo.vue";
import { currentUser } from "@/lib/api";
import { useAuthSession } from "@/lib/authSession";
import { useTheme } from "@/lib/theme";
import type { Component } from "vue";

interface NavigationItem { label: string; to: string; icon: Component }

const props = defineProps<{ eyebrow: string; title: string; navigation: NavigationItem[]; backTo?: string; themeMenu?: boolean }>();
const route = useRoute();
const menuOpen = ref(false);
const avatarMenuOpen = ref(false);
const user = computed(() => currentUser());
const initials = computed(() => user.value?.name?.split(/\s+/).map((part) => part[0]).join("").slice(0, 2).toUpperCase() || "AP");
const { signingOut, signOut } = useAuthSession();
const { theme, setTheme } = useTheme();
const active = (to: string) => route.path === to || (to !== props.navigation[0]?.to && route.path.startsWith(`${to}/`));
</script>

<template>
  <div class="min-h-screen bg-bg text-ink">
    <aside class="fixed inset-y-0 left-0 z-40 w-[260px] border-r border-border bg-bg-card transition-transform lg:translate-x-0" :class="menuOpen ? 'translate-x-0' : '-translate-x-full'">
      <div class="flex h-[72px] items-center justify-between border-b border-border px-5">
        <Logo />
        <button class="text-ink3 lg:hidden" @click="menuOpen = false"><X :size="20" /></button>
      </div>
      <div class="px-4 py-5">
        <div class="mb-5 rounded-xl bg-bg-soft px-3.5 py-3">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue">{{ eyebrow }}</p>
          <p class="mt-1 text-[14px] font-semibold text-ink">{{ title }}</p>
        </div>
        <nav class="space-y-1">
          <RouterLink
            v-for="item in navigation"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium transition"
            :class="active(item.to) ? 'bg-blue text-white shadow-sm' : 'text-ink2 hover:bg-bg-soft hover:text-ink'"
            @click="menuOpen = false"
          >
            <component :is="item.icon" :size="17" /> {{ item.label }}
          </RouterLink>
        </nav>
      </div>
      <div class="absolute inset-x-4 bottom-4 border-t border-border pt-4">
        <RouterLink v-if="backTo" :to="backTo" class="mb-2 flex items-center gap-2 rounded-xl px-3 py-2.5 text-[12px] font-medium text-ink3 hover:bg-bg-soft hover:text-ink">
          <ChevronLeft :size="15" /> Back to learning app
        </RouterLink>
        <button class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-[12px] font-medium text-ink3 hover:bg-pink-light hover:text-pink" :disabled="signingOut" @click="signOut()">
          <LogOut :size="15" /> {{ signingOut ? "Signing out…" : "Sign out" }}
        </button>
      </div>
    </aside>

    <div v-if="menuOpen" class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="menuOpen = false" />

    <div class="lg:pl-[260px]">
      <header class="sticky top-0 z-20 flex h-[72px] items-center justify-between border-b border-border bg-bg-card/95 px-4 backdrop-blur md:px-7">
        <div class="flex items-center gap-3">
          <button class="grid h-9 w-9 place-items-center rounded-xl border border-border text-ink2 lg:hidden" @click="menuOpen = true"><Menu :size="19" /></button>
          <div>
            <p class="text-[11px] font-medium text-ink3">{{ eyebrow }}</p>
            <h1 class="text-[17px] font-bold tracking-[-0.02em] text-ink">{{ title }}</h1>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="hidden text-right sm:block">
            <p class="text-[12px] font-semibold text-ink">{{ user?.name }}</p>
            <p class="text-[10px] text-ink3">{{ user?.email }}</p>
          </div>
          <div class="relative">
            <button v-if="themeMenu" type="button" class="grid h-9 w-9 place-items-center rounded-xl bg-violet-light text-[11px] font-bold text-violet transition hover:ring-2 hover:ring-violet/20" aria-label="Appearance" :aria-expanded="avatarMenuOpen" @click="avatarMenuOpen = !avatarMenuOpen">{{ initials }}</button>
            <div v-else class="grid h-9 w-9 place-items-center rounded-xl bg-violet-light text-[11px] font-bold text-violet">{{ initials }}</div>
            <div v-if="themeMenu && avatarMenuOpen" class="absolute right-0 mt-2 w-40 overflow-hidden rounded-xl border border-border bg-bg-card p-1.5 shadow-xl">
              <button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[12px] hover:bg-bg-soft" @click="setTheme('light'); avatarMenuOpen = false"><Sun :size="14" /> Light mode <Check v-if="theme === 'light'" :size="13" class="ml-auto text-blue" /></button>
              <button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[12px] hover:bg-bg-soft" @click="setTheme('dark'); avatarMenuOpen = false"><Moon :size="14" /> Dark mode <Check v-if="theme === 'dark'" :size="13" class="ml-auto text-blue" /></button>
            </div>
          </div>
        </div>
      </header>
      <main class="mx-auto max-w-[1500px] p-4 md:p-7"><slot /></main>
    </div>
  </div>
</template>
