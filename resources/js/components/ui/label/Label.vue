<script setup lang="ts">
import { Label as RekaLabel, type LabelProps } from "reka-ui";
import { cn } from "@/lib/utils";
import { labelVariants } from ".";

const props = defineProps<LabelProps & { class?: string }>();
</script>

<template>
  <RekaLabel v-bind="props" :class="cn(labelVariants(), props.class)">
    <slot />
  </RekaLabel>
</template>
