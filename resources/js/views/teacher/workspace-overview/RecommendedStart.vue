<script setup lang="ts">
import { useRouter } from "vue-router";
import { ArrowRight } from "lucide-vue-next";

const props = defineProps<{ hasClass: boolean; subject?: string }>();
const router = useRouter();
const label = props.hasClass ? "Start" : "Create First Class";

function onClick() {
  if (!props.hasClass) {
    router.push("/teacher/workspace/classes");
    return;
  }
  router.push({
    path: "/teacher/workspace/assessments/new",
    query: props.subject ? { subject: props.subject, prefill: "recommended" } : {},
  });
}
</script>
<template>
  <button class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13px] font-semibold px-4 py-2 rounded-full hover:bg-violet-700 transition" @click="onClick">
    {{ label }} <ArrowRight :size="14" />
  </button>
</template>
