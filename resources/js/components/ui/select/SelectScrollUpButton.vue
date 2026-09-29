<script setup lang="ts">
import { SelectScrollUpButton as RekaBtn, type SelectScrollUpButtonProps } from "reka-ui";
import { ChevronUp } from "lucide-vue-next";
import { cn } from "@/lib/utils";
const props = defineProps<SelectScrollUpButtonProps & { class?: string }>();
</script>
<template>
  <RekaBtn v-bind="props" :class="cn('flex cursor-default items-center justify-center py-1', props.class)">
    <ChevronUp class="h-4 w-4" />
  </RekaBtn>
</template>
