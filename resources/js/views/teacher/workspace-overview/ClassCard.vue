<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import { Users, TrendingUp } from "lucide-vue-next";
import type { TeacherClass } from "@/lib/teacherStore";

const props = defineProps<{ c: TeacherClass }>();
const router = useRouter();
const meta = computed(() => [props.c.subject, props.c.qualification, props.c.board].filter(Boolean).join(" · ") || "—");

function createAssignment() {
  router.push({
    path: "/teacher/workspace/assessments/new",
    query: props.c.subject ? { subject: props.c.subject, prefill: props.c.name } : { prefill: props.c.name },
  });
}
</script>
<template>
  <div class="bg-bg-card border border-border rounded-[14px] p-4 hover:border-violet-300 transition">
    <div class="flex items-start justify-between gap-3 mb-3">
      <div class="min-w-0">
        <div class="text-[15px] font-bold text-ink tracking-[-0.01em] truncate">{{ c.name || "Untitled class" }}</div>
        <div class="text-[12.5px] text-ink3 font-light mt-0.5">{{ meta }}</div>
      </div>
      <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-violet-50 text-violet-700">{{ c.year || "Year —" }}</span>
    </div>
    <div class="flex items-center gap-4 text-[12px] text-ink3">
      <span class="inline-flex items-center gap-1.5"><Users :size="12" /> {{ c.size || "—" }} students</span>
      <span class="inline-flex items-center gap-1.5"><TrendingUp :size="12" /> Readiness pending</span>
    </div>
    <div class="mt-4 flex gap-2">
      <RouterLink to="/teacher/workspace/classes" class="flex-1 text-[12.5px] font-semibold px-3 py-2 rounded-full bg-violet-600 text-white hover:bg-violet-700 transition text-center">
        Open Class
      </RouterLink>
      <button class="flex-1 text-[12.5px] font-semibold px-3 py-2 rounded-full bg-bg-soft text-ink2 border border-border hover:border-violet-300 transition" @click="createAssignment">
        Create Assignment
      </button>
    </div>
  </div>
</template>
