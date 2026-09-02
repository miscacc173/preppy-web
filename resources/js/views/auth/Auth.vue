<script setup lang="ts">
/**
 * Shared login/signup form, ported from login.tsx's exported `Auth` component
 * (source has signup.tsx reuse it via `<Auth mode="signup" />`; same reuse here).
 */
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import Logo from "@/components/brand/Logo.vue";
import { ArrowRight } from "lucide-vue-next";
import { ApiError, defaultAuthenticatedPath, fetchCurrentUser, hydrateAppState, login, register, requestSignupEmailVerification, verifySignupEmail } from "@/lib/api";
import AcademicCatalogFields from "@/components/catalog/AcademicCatalogFields.vue";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiCatalog } from "@/data/models/platform";
import { academicSelectionComplete } from "@/data/academicSelection";

const props = defineProps<{ mode: "login" | "signup" }>();

const router = useRouter();
const email = ref("");
const password = ref("");
const name = ref("");
const loading = ref(false);
const error = ref("");
const catalog = ref<UiCatalog | null>(null);
const courseId = ref<number | null>(null);
const boardId = ref<number | null>(null);
const subjectIds = ref<number[]>([]);
const signupStep = ref<"details" | "otp">("details");
const verificationChallengeId = ref("");
const verificationCode = ref("");
const verificationGrant = ref("");
const verifiedEmail = ref("");
const academicComplete = computed(() => props.mode !== "signup" || academicSelectionComplete(catalog.value, courseId.value, boardId.value, subjectIds.value));

onMounted(async () => {
  if (props.mode !== "signup") return;
  try { catalog.value = await platformRepository.catalog(); }
  catch (reason) { error.value = reason instanceof Error ? reason.message : "Academic options could not be loaded."; }
});

async function submit() {
  loading.value = true;
  error.value = "";
  try {
    if (props.mode === "signup") {
      if (signupStep.value === "details") {
        const response = await requestSignupEmailVerification(email.value);
        verifiedEmail.value = response.data.email;
        verificationChallengeId.value = response.data.id;
        verificationCode.value = "";

        if (response.data.development_bypass && response.data.verification_grant) {
          verificationGrant.value = response.data.verification_grant;
          await createVerifiedAccount();
        } else {
          signupStep.value = "otp";
        }
        return;
      }

      const response = await verifySignupEmail(verificationChallengeId.value, verificationCode.value);
      verificationGrant.value = response.data.verification_grant;
      verifiedEmail.value = response.data.email;
      await createVerifiedAccount();
      return;
    }

    await login(email.value, password.value);
    const user = await fetchCurrentUser();
    await hydrateAppState();
    await router.push(defaultAuthenticatedPath(user));
  } catch (reason) {
    error.value = apiMessage(reason);
  } finally { loading.value = false; }
}

async function createVerifiedAccount() {
  await register(name.value, verifiedEmail.value, password.value, verificationGrant.value, {
    course_id: courseId.value!,
    exam_board_id: boardId.value!,
    subject_ids: subjectIds.value,
  });
  await fetchCurrentUser();
  await hydrateAppState();
  await router.push("/onboarding");
}

async function resendSignupVerification() {
  loading.value = true;
  error.value = "";
  try {
    const response = await requestSignupEmailVerification(verifiedEmail.value);
    verificationChallengeId.value = response.data.id;
    verificationCode.value = "";

    if (response.data.development_bypass && response.data.verification_grant) {
      verificationGrant.value = response.data.verification_grant;
      await createVerifiedAccount();
    }
  } catch (reason) {
    error.value = apiMessage(reason);
  } finally {
    loading.value = false;
  }
}

function changeSignupEmail() {
  signupStep.value = "details";
  verificationChallengeId.value = "";
  verificationCode.value = "";
  verificationGrant.value = "";
  error.value = "";
}

function apiMessage(reason: unknown) {
  if (reason instanceof ApiError) {
    return Object.values(reason.errors)[0]?.[0] || reason.message;
  }
  return "Unable to connect to the server.";
}
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <div class="px-6 py-5 border-b border-border bg-bg-card">
      <Logo />
    </div>
    <div class="flex-1 grid place-items-center px-5 py-10">
      <div class="w-full max-w-[420px]">
        <h1 class="text-[28px] font-bold text-ink tracking-[-0.025em]">{{ mode === "signup" ? "Create your account" : "Welcome back" }}</h1>
        <p class="text-[14px] text-ink3 mt-1.5 mb-7 font-light">
          {{ mode === "signup" ? "Create your AiStar Prep student account." : "Sign in to your AiStar Prep account." }}
        </p>

        <a href="/auth/google" class="w-full mb-4 flex items-center justify-center gap-2.5 bg-bg-card border border-border rounded-md py-3 text-[14px] font-medium text-ink2 hover:bg-bg-soft">
          <svg viewBox="0 0 48 48" width="18" height="18"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.6-6 8-11.3 8a12 12 0 1 1 0-24c3 0 5.7 1.1 7.8 3l5.7-5.7C33.6 6.1 29.1 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.6 16 18.9 13 24 13c3 0 5.7 1.1 7.8 3l5.7-5.7C33.6 6.1 29.1 4 24 4 16.3 4 9.7 8.4 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.8-2 13.2-5.2l-6.1-5.2c-2 1.4-4.4 2.4-7.1 2.4-5.3 0-9.7-3.4-11.3-8.1l-6.5 5C9.6 39.5 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.6l6.1 5.2C40.7 36.9 44 31 44 24c0-1.2-.1-2.3-.4-3.5z"/></svg>
          Continue with Google
        </a>

        <div class="flex items-center gap-3 my-5">
          <div class="flex-1 h-px bg-border" />
          <span class="text-[11px] text-ink4 uppercase tracking-wider">or</span>
          <div class="flex-1 h-px bg-border" />
        </div>

        <form class="space-y-3.5" @submit.prevent="submit">
          <template v-if="mode !== 'signup' || signupStep === 'details'">
          <div v-if="mode === 'signup'">
            <label class="block text-[12px] font-medium text-ink2 mb-1.5">Name</label>
            <input v-model="name" required class="w-full bg-bg-card border border-border rounded-md px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-blue" />
          </div>
          <AcademicCatalogFields v-if="mode === 'signup'" :catalog="catalog" v-model:course-id="courseId" v-model:board-id="boardId" v-model:subject-ids="subjectIds" />
          <div>
            <label class="block text-[12px] font-medium text-ink2 mb-1.5">Email</label>
            <input v-model="email" type="email" required class="w-full bg-bg-card border border-border rounded-md px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-blue" />
          </div>
          </template>

          <div v-if="mode === 'signup' && signupStep === 'otp'" class="space-y-3.5">
            <div class="rounded-lg border border-border bg-bg-soft px-4 py-3">
              <p class="text-[12px] text-ink3">Verification code sent to</p>
              <p class="mt-0.5 text-[14px] font-semibold text-ink">{{ verifiedEmail }}</p>
            </div>
            <div>
              <label class="block text-[12px] font-medium text-ink2 mb-1.5">Six-digit verification code</label>
              <input
                v-model="verificationCode"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
                placeholder="000000"
                class="w-full bg-bg-card border border-border rounded-md px-3.5 py-2.5 text-[18px] font-mono tracking-[0.22em] text-center text-ink outline-none focus:border-blue"
                @input="verificationCode = verificationCode.replace(/\D/g, '').slice(0, 6)"
              />
            </div>
            <div class="flex items-center justify-between text-[12.5px]">
              <button type="button" class="text-ink3 hover:text-blue" :disabled="loading" @click="changeSignupEmail">Change email</button>
              <button type="button" class="text-blue hover:opacity-80" :disabled="loading" @click="resendSignupVerification">Resend code</button>
            </div>
          </div>
          <div>
            <label class="block text-[12px] font-medium text-ink2 mb-1.5">Password</label>
            <input v-model="password" type="password" required minlength="8" class="w-full bg-bg-card border border-border rounded-md px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-blue" />
          </div>

          <p v-if="error" class="text-[13px] text-pink">{{ error }}</p>

          <button type="submit" :disabled="loading || !academicComplete || (mode === 'signup' && signupStep === 'otp' && verificationCode.length !== 6)" class="w-full mt-2 flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold py-3 rounded-md hover:opacity-90 disabled:opacity-60">
            {{ loading ? "Please wait…" : mode === "signup" ? (signupStep === "otp" ? "Verify & create account" : "Verify email") : "Sign in" }} <ArrowRight :size="15" />
          </button>
        </form>

        <p class="text-center text-[13px] text-ink3 mt-6">
          <template v-if="mode === 'signup'">
            Already have an account? <RouterLink to="/login" class="text-blue font-medium">Sign in</RouterLink>
          </template>
          <template v-else>
            New to AiStar Prep? <RouterLink to="/signup" class="text-blue font-medium">Create account</RouterLink>
          </template>
        </p>
      </div>
    </div>
  </div>
</template>
