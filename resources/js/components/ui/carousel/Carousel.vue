<script setup lang="ts">
/**
 * Ported from carousel.tsx using `embla-carousel-vue` (direct Vue equivalent of
 * `embla-carousel-react`) instead of a React context — state lives in refs and is
 * shared to children via provide/inject.
 */
import { provide, ref, watch, onMounted, onBeforeUnmount } from "vue";
import emblaCarouselVue from "embla-carousel-vue";
import type { EmblaOptionsType, EmblaPluginType } from "embla-carousel";
import { cn } from "@/lib/utils";
import { CAROUSEL_CONTEXT_KEY } from "./context";

const props = withDefaults(
  defineProps<{
    opts?: EmblaOptionsType;
    plugins?: EmblaPluginType[];
    orientation?: "horizontal" | "vertical";
    class?: string;
  }>(),
  { orientation: "horizontal" },
);

const emit = defineEmits<{ "init-api": [api: ReturnType<typeof emblaCarouselVue>[1]["value"]] }>();

const [emblaRef, emblaApi] = emblaCarouselVue(
  { ...props.opts, axis: props.orientation === "horizontal" ? "x" : "y" },
  props.plugins,
);

const canScrollPrev = ref(false);
const canScrollNext = ref(false);

function onSelect() {
  const api = emblaApi.value;
  if (!api) return;
  canScrollPrev.value = api.canScrollPrev();
  canScrollNext.value = api.canScrollNext();
}

function scrollPrev() {
  emblaApi.value?.scrollPrev();
}
function scrollNext() {
  emblaApi.value?.scrollNext();
}

function handleKeyDown(event: KeyboardEvent) {
  if (event.key === "ArrowLeft") {
    event.preventDefault();
    scrollPrev();
  } else if (event.key === "ArrowRight") {
    event.preventDefault();
    scrollNext();
  }
}

watch(emblaApi, (api) => {
  if (!api) return;
  onSelect();
  api.on("reInit", onSelect);
  api.on("select", onSelect);
  emit("init-api", api);
});

provide(CAROUSEL_CONTEXT_KEY, {
  emblaRef,
  emblaApi,
  orientation: props.orientation,
  scrollPrev,
  scrollNext,
  canScrollPrev,
  canScrollNext,
});
</script>

<template>
  <div
    :class="cn('relative', props.class)"
    role="region"
    aria-roledescription="carousel"
    @keydown.capture="handleKeyDown"
  >
    <slot />
  </div>
</template>
