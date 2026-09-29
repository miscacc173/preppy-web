<script setup lang="ts">
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import { useFormField } from "./useFormField";

const props = defineProps<{ class?: string }>();
const { error, formItemId } = useFormField();
</script>

<template>
  <Label :for="formItemId" :class="cn(error && 'text-destructive', props.class)">
    <slot />
  </Label>
</template>
