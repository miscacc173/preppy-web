<script setup lang="ts">
import { Primitive } from "reka-ui";
import { useFormField } from "./useFormField";

const { error, formItemId, formDescriptionId, formMessageId } = useFormField();
</script>

<template>
  <Primitive
    as-child
    :id="formItemId"
    :aria-describedby="!error ? formDescriptionId : `${formDescriptionId} ${formMessageId}`"
    :aria-invalid="!!error"
  >
    <slot />
  </Primitive>
</template>
