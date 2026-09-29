<script setup lang="ts">
import { inject } from "vue";
import { ArrowLeft } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import type { ButtonVariants } from "@/components/ui/button";
import { CAROUSEL_CONTEXT_KEY } from "./context";

const props = withDefaults(
  defineProps<{ variant?: ButtonVariants["variant"]; size?: ButtonVariants["size"]; class?: string }>(),
  { variant: "outline", size: "icon" },
);
const ctx = inject(CAROUSEL_CONTEXT_KEY)!;
</script>

<template>
  <Button
    :variant="variant"
    :size="size"
    :class="
      cn(
        'absolute h-8 w-8 rounded-full',
        ctx.orientation === 'horizontal' ? '-left-12 top-1/2 -translate-y-1/2' : '-top-12 left-1/2 -translate-x-1/2 rotate-90',
        props.class,
      )
    "
    :disabled="!ctx.canScrollPrev.value"
    @click="ctx.scrollPrev"
  >
    <ArrowLeft class="h-4 w-4" />
    <span class="sr-only">Previous slide</span>
  </Button>
</template>
