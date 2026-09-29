<script setup lang="ts">
import { onBeforeUnmount, watch } from "vue";

const props = withDefaults(defineProps<{ open: boolean; maxWidth?: string; closeOnBackdrop?: boolean }>(), {
  maxWidth: "max-w-lg",
  closeOnBackdrop: true,
});
const emit = defineEmits<{ close: [] }>();
function close() { emit("close"); }
function onKeydown(event: KeyboardEvent) { if (event.key === "Escape") close(); }
watch(() => props.open, (open) => {
  document.body.style.overflow = open ? "hidden" : "";
  if (open) window.addEventListener("keydown", onKeydown); else window.removeEventListener("keydown", onKeydown);
}, { immediate: true });
onBeforeUnmount(() => { document.body.style.overflow = ""; window.removeEventListener("keydown", onKeydown); });
</script>

<template>
  <Teleport to="body">
    <Transition name="ops-modal">
      <div v-if="open" class="fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-ink/45 p-4 backdrop-blur-[3px]" role="dialog" aria-modal="true" @mousedown.self="closeOnBackdrop && close()">
        <div class="ops-modal-card my-auto w-full" :class="maxWidth" @mousedown.stop>
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.ops-modal-enter-active, .ops-modal-leave-active { transition: opacity .22s ease; }
.ops-modal-enter-active .ops-modal-card, .ops-modal-leave-active .ops-modal-card { transition: transform .26s cubic-bezier(.2,.8,.2,1), opacity .2s ease; }
.ops-modal-enter-from, .ops-modal-leave-to { opacity: 0; }
.ops-modal-enter-from .ops-modal-card { opacity: 0; transform: translateY(18px) scale(.965); }
.ops-modal-leave-to .ops-modal-card { opacity: 0; transform: translateY(10px) scale(.98); }
@media (prefers-reduced-motion: reduce) {
  .ops-modal-enter-active, .ops-modal-leave-active, .ops-modal-enter-active .ops-modal-card, .ops-modal-leave-active .ops-modal-card { transition-duration: .01ms; }
}
</style>
