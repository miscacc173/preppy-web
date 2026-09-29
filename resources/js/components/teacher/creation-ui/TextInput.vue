<script setup lang="ts">
import { inputCx } from ".";
defineOptions({ inheritAttrs: false });
const model = defineModel<string>();
</script>
<template>
  <input v-model="model" v-bind="$attrs" :class="inputCx" />
</template>
