<script setup lang="ts">
import { RadioGroupRoot, type RadioGroupRootProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<RadioGroupRootProps & { class?: string }>();
</script>

<template>
  <RadioGroupRoot v-bind="props" :class="cn('grid gap-2', props.class)">
    <slot />
  </RadioGroupRoot>
</template>
