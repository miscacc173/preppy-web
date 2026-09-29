<script setup lang="ts">
import Chip from "./Chip.vue";

defineProps<{ options: readonly string[] }>();
const values = defineModel<string[]>({ required: true });

function toggle(o: string) {
  values.value = values.value.includes(o) ? values.value.filter((v) => v !== o) : [...values.value, o];
}
</script>
<template>
  <div class="flex flex-wrap gap-2">
    <Chip v-for="o in options" :key="o" :active="values.includes(o)" @click="toggle(o)">{{ o }}</Chip>
  </div>
</template>
