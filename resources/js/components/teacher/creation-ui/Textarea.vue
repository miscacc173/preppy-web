<script setup lang="ts">
import { inputCx } from ".";
defineOptions({ inheritAttrs: false });
const model = defineModel<string>();
</script>
<template>
  <textarea v-model="model" v-bind="$attrs" :class="`${inputCx} min-h-[80px]`" />
</template>
