<script setup lang="ts">
import { inputCx } from ".";
defineOptions({ inheritAttrs: false });
const model = defineModel<number | string>();
</script>
<template>
  <input v-model="model" type="number" v-bind="$attrs" :class="inputCx" />
</template>
