<script setup lang="ts">
import { PinInputRoot, type PinInputRootProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<PinInputRootProps & { class?: string; containerClass?: string }>();
</script>

<template>
  <PinInputRoot v-bind="props" :class="cn('flex items-center gap-2 has-[:disabled]:opacity-50', props.containerClass, props.class)">
    <slot />
  </PinInputRoot>
</template>
