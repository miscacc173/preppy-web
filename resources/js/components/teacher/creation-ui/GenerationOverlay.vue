<script setup lang="ts">
defineProps<{ messages: string[]; active: boolean }>();
</script>
<template>
  <div v-if="active" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 grid place-items-center p-6">
    <div class="bg-bg-card border border-border rounded-2xl p-6 md:p-8 max-w-[440px] w-full shadow-[0_24px_60px_rgba(15,23,42,0.2)]">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-violet-50 grid place-items-center">
          <span class="w-4 h-4 rounded-full border-2 border-violet-600 border-t-transparent animate-spin" />
        </div>
        <div>
          <div class="text-[15px] font-bold text-ink tracking-[-0.01em]">Generating…</div>
          <div class="text-[12px] text-ink3 font-light">This usually takes a few seconds.</div>
        </div>
      </div>
      <ul class="space-y-2">
        <li
          v-for="(m, i) in messages"
          :key="i"
          class="text-[13px] text-ink2 flex items-start gap-2 opacity-0 animate-[fadeIn_0.4s_ease_forwards]"
          :style="{ animationDelay: `${i * 500}ms` }"
        >
          <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-violet-500 shrink-0" />
          {{ m }}
        </li>
      </ul>
    </div>
  </div>
</template>

<style>
@keyframes fadeIn {
  to {
    opacity: 1;
  }
}
</style>
