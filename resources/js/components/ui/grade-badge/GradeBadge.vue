<script setup lang="ts">
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { normalizeGrade, GRADE_SIZE_STYLES, type Grade, type GradeBadgeSize } from ".";

const props = withDefaults(
  defineProps<{
    grade: string | Grade;
    /** Optional prefix such as "Pred.", "Target", "Avg" — kept consistent across the app. */
    label?: string;
    size?: GradeBadgeSize;
    class?: string;
    title?: string;
  }>(),
  { size: "md" },
);

const g = computed(() => normalizeGrade(props.grade));
</script>

<template>
  <span
    :title="title"
    :class="
      cn(
        'inline-flex items-center gap-1 font-mono font-medium text-violet bg-violet-light border border-[#DDD0F8] rounded-full whitespace-nowrap',
        GRADE_SIZE_STYLES[size],
        props.class,
      )
    "
  >
    <span v-if="label" class="opacity-70">{{ label }}</span>
    <span>{{ g }}</span>
  </span>
</template>
