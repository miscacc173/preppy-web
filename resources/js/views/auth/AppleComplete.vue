<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import Logo from "@/components/brand/Logo.vue";
import { exchangeWebSession, hydrateAppState, defaultAuthenticatedPath } from "@/lib/api";

const route = useRoute();
const router = useRouter();
const error = ref("");

onMounted(async () => {
  try {
    const user = await exchangeWebSession();
    await hydrateAppState();
    const needsOnboarding = route.query.onboarding === "1";
    await router.replace(needsOnboarding ? "/onboarding?source=apple" : defaultAuthenticatedPath(user));
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Apple sign-in could not be completed.";
  }
});
</script>

<template>
  <div class="min-h-screen bg-bg grid place-items-center px-5">
    <div class="w-full max-w-sm rounded-2xl border border-border bg-bg-card p-7 text-center shadow-sm">
      <div class="flex justify-center"><Logo /></div>
      <p v-if="!error" class="mt-5 text-[13px] text-ink3">Completing your Apple sign-in…</p>
      <template v-else>
        <p class="mt-5 text-[13px] text-pink">{{ error }}</p>
        <RouterLink to="/login" class="mt-4 inline-flex rounded-md bg-blue px-4 py-2 text-[13px] font-semibold text-white">Return to sign in</RouterLink>
      </template>
    </div>
  </div>
</template>
