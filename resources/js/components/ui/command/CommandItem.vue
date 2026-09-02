<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, watch } from "vue";
import { cn } from "@/lib/utils";
import { COMMAND_CONTEXT_KEY } from "./context";

const props = defineProps<{ class?: string; value?: string; disabled?: boolean }>();
const emit = defineEmits<{ select: [] }>();

const ctx = inject(COMMAND_CONTEXT_KEY);

// Plain case-insensitive substring match against `value` (falls back to
// matching everything if no `value` is given — same "always visible" default
// cmdk uses when it can't derive text content).
const matches = computed(() => {
  if (!ctx || !ctx.search.value) return true;
  if (!props.value) return true;
  return props.value.toLowerCase().includes(ctx.search.value.toLowerCase());
});

watch(
  matches,
  (isVisible, wasVisible) => {
    if (!ctx) return;
    if (isVisible && !wasVisible) ctx.visibleCount.value++;
    if (!isVisible && wasVisible) ctx.visibleCount.value--;
  },
  { immediate: false },
);

onMounted(() => {
  if (ctx && matches.value) ctx.visibleCount.value++;
});
onBeforeUnmount(() => {
  if (ctx && matches.value) ctx.visibleCount.value--;
});

function onSelect() {
  if (props.disabled) return;
  emit("select");
}
</script>

<template>
  <div
    v-show="matches"
    cmdk-item=""
    :data-disabled="disabled ? 'true' : undefined"
    :class="
      cn(
        'relative flex cursor-default gap-2 select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none data-[disabled=true]:pointer-events-none hover:bg-accent hover:text-accent-foreground data-[disabled=true]:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
        props.class,
      )
    "
    @click="onSelect"
  >
    <slot />
  </div>
</template>
