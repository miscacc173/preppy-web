<script setup lang="ts">
import { inject } from "vue";
import { Search } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { COMMAND_CONTEXT_KEY } from "./context";

const props = defineProps<{ class?: string }>();
const ctx = inject(COMMAND_CONTEXT_KEY);
</script>

<template>
  <div class="flex items-center border-b px-3" cmdk-input-wrapper="">
    <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
    <input
      v-model="ctx!.search.value"
      :class="cn('flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50', props.class)"
    />
  </div>
</template>
