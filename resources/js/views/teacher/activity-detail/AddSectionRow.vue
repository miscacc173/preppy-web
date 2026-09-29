<script setup lang="ts">
import { computed, ref } from "vue";
import { Plus } from "lucide-vue-next";
import type { ActivitySection } from "@/lib/teacherStore";

const props = defineProps<{ existing: ActivitySection["kind"][]; kinds: readonly ActivitySection["kind"][] }>();
const emit = defineEmits<{ add: [kind: ActivitySection["kind"]] }>();

const open = ref(false);
const available = computed(() => props.kinds.filter((k) => !props.existing.includes(k)));
const shown = computed(() => (available.value.length ? available.value : props.kinds));

function pick(k: ActivitySection["kind"]) {
  emit("add", k);
  open.value = false;
}
</script>

<template>
  <div class="relative">
    <button
      class="w-full inline-flex items-center justify-center gap-1.5 text-[13px] font-semibold text-violet-700 bg-violet-50 hover:bg-violet-100 border border-dashed border-violet-200 rounded-[14px] py-3 transition"
      @click="open = !open"
    >
      <Plus :size="14" /> Add a section
    </button>
    <div v-if="open" class="absolute left-1/2 -translate-x-1/2 mt-2 bg-bg-card border border-border rounded-xl shadow-lg p-1.5 z-10 w-[220px]">
      <button
        v-for="k in shown"
        :key="k"
        class="w-full text-left px-3 py-1.5 rounded-md text-[13px] text-ink2 hover:bg-violet-50 hover:text-violet-700"
        @click="pick(k)"
      >
        {{ k.charAt(0).toUpperCase() + k.slice(1) }}
      </button>
    </div>
  </div>
</template>
