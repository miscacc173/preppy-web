<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref, watch, type CSSProperties } from "vue";
import { cn } from "@/lib/utils";
import { useIsMobile } from "@/composables/useIsMobile";
import { TooltipProvider } from "@/components/ui/tooltip";
import { SIDEBAR_CONTEXT_KEY } from "./context";
import {
  SIDEBAR_COOKIE_MAX_AGE,
  SIDEBAR_COOKIE_NAME,
  SIDEBAR_KEYBOARD_SHORTCUT,
  SIDEBAR_WIDTH,
  SIDEBAR_WIDTH_ICON,
} from "./constants";

const props = withDefaults(
  defineProps<{
    defaultOpen?: boolean;
    open?: boolean;
    class?: string;
    style?: CSSProperties;
  }>(),
  { defaultOpen: true },
);

const emit = defineEmits<{ "update:open": [open: boolean] }>();

const isMobile = useIsMobile();
const openMobile = ref(false);

const _open = ref(props.open ?? props.defaultOpen);
watch(
  () => props.open,
  (val) => {
    if (val !== undefined) _open.value = val;
  },
);

function setOpen(value: boolean) {
  _open.value = value;
  emit("update:open", value);
  document.cookie = `${SIDEBAR_COOKIE_NAME}=${value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`;
}

function toggleSidebar() {
  if (isMobile.value) {
    openMobile.value = !openMobile.value;
  } else {
    setOpen(!_open.value);
  }
}

function handleKeyDown(event: KeyboardEvent) {
  if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
    event.preventDefault();
    toggleSidebar();
  }
}

onMounted(() => window.addEventListener("keydown", handleKeyDown));
onBeforeUnmount(() => window.removeEventListener("keydown", handleKeyDown));

const state = computed<"expanded" | "collapsed">(() => (_open.value ? "expanded" : "collapsed"));

provide(SIDEBAR_CONTEXT_KEY, {
  get state() {
    return state.value;
  },
  open: _open,
  setOpen,
  openMobile,
  setOpenMobile: (v: boolean) => (openMobile.value = v),
  isMobile,
  toggleSidebar,
});

const mergedStyle = computed(() => ({
  "--sidebar-width": SIDEBAR_WIDTH,
  "--sidebar-width-icon": SIDEBAR_WIDTH_ICON,
  ...props.style,
}));
</script>

<template>
  <TooltipProvider :delay-duration="0">
    <div
      :style="mergedStyle"
      :class="cn('group/sidebar-wrapper flex min-h-svh w-full has-[[data-variant=inset]]:bg-sidebar', props.class)"
    >
      <slot />
    </div>
  </TooltipProvider>
</template>
