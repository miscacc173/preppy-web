<script setup lang="ts">
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";
import type { ButtonVariants } from "@/components/ui/button";

const props = withDefaults(
  defineProps<{ isActive?: boolean; size?: ButtonVariants["size"]; class?: string }>(),
  { size: "icon" },
);
</script>
<template>
  <a
    :aria-current="isActive ? 'page' : undefined"
    :class="cn(buttonVariants({ variant: isActive ? 'outline' : 'ghost', size }), props.class)"
  >
    <slot />
  </a>
</template>
