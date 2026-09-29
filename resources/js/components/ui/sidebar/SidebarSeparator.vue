<script setup lang="ts">
import { Separator } from "@/components/ui/separator";
import { cn } from "@/lib/utils";
const props = defineProps<{ class?: string }>();
</script>
<template>
  <Separator data-sidebar="separator" :class="cn('mx-2 w-auto bg-sidebar-border', props.class)" />
</template>
