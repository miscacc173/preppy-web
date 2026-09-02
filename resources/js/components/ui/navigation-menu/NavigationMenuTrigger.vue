<script setup lang="ts">
import { NavigationMenuTrigger as RekaTrigger, type NavigationMenuTriggerProps } from "reka-ui";
import { ChevronDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { navigationMenuTriggerStyle } from ".";

const props = defineProps<NavigationMenuTriggerProps & { class?: string }>();
</script>
<template>
  <RekaTrigger v-bind="props" :class="cn(navigationMenuTriggerStyle(), 'group', props.class)">
    <slot />
    <ChevronDown class="relative top-[1px] ml-1 h-3 w-3 transition duration-300 group-data-[state=open]:rotate-180" aria-hidden="true" />
  </RekaTrigger>
</template>
