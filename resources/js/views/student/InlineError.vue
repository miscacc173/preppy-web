<script setup lang="ts">
import { AlertCircle } from "lucide-vue-next";
defineProps<{ show: boolean; message?: string }>();
</script>
<template>
  <p v-if="show" data-error="true" class="mt-2 flex items-center gap-1.5 text-[12.5px] text-red-600 font-medium">
    <AlertCircle :size="13" /> {{ message }}
  </p>
</template>
