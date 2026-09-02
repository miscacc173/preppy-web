<script setup lang="ts">
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { useFormField } from "./useFormField";

const props = defineProps<{ class?: string }>();
const { error, formMessageId } = useFormField();
const body = computed(() => (error.value ? String(error.value) : undefined));
</script>

<template>
  <p v-if="body || $slots.default" :id="formMessageId" :class="cn('text-[0.8rem] font-medium text-destructive', props.class)">
    {{ body }}<slot v-if="!body" />
  </p>
</template>
