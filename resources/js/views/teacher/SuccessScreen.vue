<script setup lang="ts">
/**
 * Ported from teacher.workspace.assessments.$id.tsx's exported `SuccessScreen`
 * (shared across the assessments/activities/practice "new" flows in the
 * source via a cross-route import). Here it's its own component under
 * views/teacher/, imported the same way by each "new" flow's main view.
 */
import { CheckCircle2 } from "lucide-vue-next";

defineProps<{
  title: string;
  primary?: { label: string; onClick?: () => void };
  secondary?: { label: string; to: string };
  tertiary?: { label: string; to: string };
}>();
</script>

<template>
  <div class="bg-bg-card border border-border rounded-[16px] p-8 md:p-10 text-center">
    <div class="w-14 h-14 rounded-full bg-emerald-50 grid place-items-center mx-auto mb-4">
      <CheckCircle2 :size="28" class="text-emerald-600" />
    </div>
    <h2 class="text-[20px] font-bold text-ink tracking-[-0.01em]">{{ title }}</h2>
    <p class="text-[13px] text-ink3 font-light mt-1.5">You'll see this in the list once students start engaging with it.</p>
    <div class="mt-6 flex flex-wrap gap-2 justify-center">
      <button v-if="primary" class="bg-violet-600 text-white text-[13.5px] font-semibold px-4 py-2 rounded-full hover:bg-violet-700" @click="primary.onClick?.()">
        {{ primary.label }}
      </button>
      <RouterLink v-if="secondary" :to="secondary.to" class="border border-border text-ink2 text-[13.5px] font-semibold px-4 py-2 rounded-full hover:border-violet-300">
        {{ secondary.label }}
      </RouterLink>
      <RouterLink v-if="tertiary" :to="tertiary.to" class="text-ink3 text-[13.5px] font-semibold px-4 py-2 rounded-full hover:text-violet-700">
        {{ tertiary.label }}
      </RouterLink>
    </div>
  </div>
</template>
