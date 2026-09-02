<script setup lang="ts">
defineProps<{ label: string }>();
const on = defineModel<boolean>("on", { required: true });
</script>
<template>
  <div class="flex items-center justify-between py-2">
    <span class="text-[13px] text-ink2">{{ label }}</span>
    <button role="switch" :aria-checked="on" :class="`relative w-9 h-5 rounded-full transition ${on ? 'bg-blue' : 'bg-bg-soft2 border border-border'}`" @click="on = !on">
      <span :class="`absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform ${on ? 'translate-x-4' : ''}`" />
    </button>
  </div>
</template>
