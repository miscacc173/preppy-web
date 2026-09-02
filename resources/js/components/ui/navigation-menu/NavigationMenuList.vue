<script setup lang="ts">
import { NavigationMenuList as RekaList, type NavigationMenuListProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps<NavigationMenuListProps & { class?: string }>();
</script>
<template>
  <RekaList v-bind="props" :class="cn('group flex flex-1 list-none items-center justify-center space-x-1', props.class)">
    <slot />
  </RekaList>
</template>
