<script setup lang="ts">
import { Check } from "lucide-vue-next";

type ChoiceItem = string | { value: string; label: string; comingSoon?: boolean };
defineProps<{ title: string; sub?: string; items: ChoiceItem[] }>();
const model = defineModel<string>({ required: true });
const valueOf = (item: ChoiceItem) => typeof item === "string" ? item : item.value;
const labelOf = (item: ChoiceItem) => typeof item === "string" ? item : item.label;
const comingSoon = (item: ChoiceItem) => typeof item !== "string" && Boolean(item.comingSoon);
</script>

<template>
  <h2 class="text-[24px] font-bold text-ink mb-1.5">{{ title }}</h2>
  <p v-if="sub" class="text-[13.5px] text-ink3 mb-6 font-light">{{ sub }}</p>
  <div class="flex flex-col gap-2">
    <button
      v-for="it in items"
      :key="valueOf(it)"
      :disabled="comingSoon(it)"
      :class="`flex items-center justify-between px-4 py-3.5 text-[14px] font-medium rounded-lg border transition text-left ${
        model === valueOf(it) ? 'bg-blue-light border-blue text-blue' : comingSoon(it) ? 'bg-bg-soft border-border text-ink3 cursor-not-allowed' : 'bg-bg-card border-border text-ink2 hover:border-blue-mid'
      }`"
      @click="model = valueOf(it)"
    >
      <span>{{ labelOf(it) }}</span>
      <span v-if="comingSoon(it)" class="rounded-full bg-gold-light px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-gold">Coming soon</span>
      <Check v-else-if="model === valueOf(it)" :size="16" />
    </button>
  </div>
</template>
