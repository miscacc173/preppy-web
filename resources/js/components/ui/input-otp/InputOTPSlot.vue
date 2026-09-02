<script setup lang="ts">
import { PinInputInput } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps<{ index: number; class?: string }>();
</script>

<template>
  <PinInputInput
    :index="index"
    :class="
      cn(
        'relative flex h-9 w-9 items-center justify-center border-y border-r border-input text-sm shadow-sm transition-all first:rounded-l-md first:border-l last:rounded-r-md text-center bg-transparent focus-visible:z-10 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none',
        props.class,
      )
    "
  />
</template>
