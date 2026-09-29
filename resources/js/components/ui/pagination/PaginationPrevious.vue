<script setup lang="ts">
import { ChevronLeft } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import PaginationLink from "./PaginationLink.vue";

const props = defineProps<{ class?: string }>();
</script>
<template>
  <PaginationLink aria-label="Go to previous page" size="default" :class="cn('gap-1 pl-2.5', props.class)">
    <ChevronLeft class="h-4 w-4" />
    <span>Previous</span>
  </PaginationLink>
</template>
