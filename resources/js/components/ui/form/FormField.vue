<script setup lang="ts">
import { provide } from "vue";
import { useField } from "vee-validate";
import { FORM_FIELD_CONTEXT_KEY } from "./context";

const props = defineProps<{ name: string }>();
provide(FORM_FIELD_CONTEXT_KEY, { name: props.name });

// Surfaces the same field-state shape react-hook-form's <Controller> gives the
// source render-prop, via vee-validate's useField, for callers that want it.
const field = useField(props.name);
defineExpose({ field });
</script>

<template>
  <slot :field="field" />
</template>
