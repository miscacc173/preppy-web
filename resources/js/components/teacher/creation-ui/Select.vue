<script setup lang="ts">
import { inputCx } from ".";
defineProps<{ options: readonly string[]; placeholder?: string }>();
const model = defineModel<string>({ required: true });
</script>
<template>
  <select v-model="model" :class="inputCx">
    <option v-if="placeholder" value="">{{ placeholder }}</option>
    <option v-for="o in options" :key="o" :value="o">{{ o }}</option>
  </select>
</template>
