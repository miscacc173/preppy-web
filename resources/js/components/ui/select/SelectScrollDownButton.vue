<script setup lang="ts">
import { SelectScrollDownButton as RekaBtn, type SelectScrollDownButtonProps } from "reka-ui";
import { ChevronDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";
const props = defineProps<SelectScrollDownButtonProps & { class?: string }>();
</script>
<template>
  <RekaBtn v-bind="props" :class="cn('flex cursor-default items-center justify-center py-1', props.class)">
    <ChevronDown class="h-4 w-4" />
  </RekaBtn>
</template>
