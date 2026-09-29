<script setup lang="ts">
import { NavigationMenuRoot, type NavigationMenuRootProps } from "reka-ui";
import { cn } from "@/lib/utils";
import NavigationMenuViewport from "./NavigationMenuViewport.vue";

const props = defineProps<NavigationMenuRootProps & { class?: string }>();
</script>
<template>
  <NavigationMenuRoot v-bind="props" :class="cn('relative z-10 flex max-w-max flex-1 items-center justify-center', props.class)">
    <slot />
    <NavigationMenuViewport />
  </NavigationMenuRoot>
</template>
