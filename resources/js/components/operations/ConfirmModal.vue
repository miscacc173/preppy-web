<script setup lang="ts">
import { AlertTriangle, LoaderCircle, Trash2, X } from "lucide-vue-next";
import OpsModal from "@/components/operations/OpsModal.vue";

withDefaults(defineProps<{ open: boolean; title?: string; message: string; confirmLabel?: string; busy?: boolean; tone?: "danger" | "warning" }>(), {
  title: "Confirm action",
  confirmLabel: "Confirm",
  busy: false,
  tone: "danger",
});
defineEmits<{ close: []; confirm: [] }>();
</script>

<template>
  <OpsModal :open="open" max-width="max-w-md" :close-on-backdrop="!busy" @close="$emit('close')">
    <div class="rounded-2xl border border-border bg-bg-card p-5 shadow-2xl">
      <div class="flex items-start gap-4">
        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl" :class="tone === 'danger' ? 'bg-pink-light text-pink' : 'bg-gold-light text-gold'">
          <Trash2 v-if="tone === 'danger'" :size="20" />
          <AlertTriangle v-else :size="20" />
        </div>
        <div class="min-w-0 flex-1"><h3 class="text-[17px] font-bold text-ink">{{ title }}</h3><p class="mt-1.5 text-[13px] leading-5 text-ink3">{{ message }}</p></div>
        <button :disabled="busy" class="rounded-lg p-1 text-ink4 transition hover:bg-bg-soft hover:text-ink" @click="$emit('close')"><X :size="18" /></button>
      </div>
      <div class="mt-6 flex justify-end gap-2 border-t border-border pt-4">
        <button :disabled="busy" class="ops-button-secondary" @click="$emit('close')">Cancel</button>
        <button :disabled="busy" class="inline-flex min-w-28 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-[12px] font-semibold text-white transition disabled:opacity-60" :class="tone === 'danger' ? 'bg-pink hover:brightness-95' : 'bg-gold hover:brightness-95'" @click="$emit('confirm')">
          <LoaderCircle v-if="busy" :size="14" class="animate-spin" />{{ busy ? 'Working…' : confirmLabel }}
        </button>
      </div>
    </div>
  </OpsModal>
</template>
