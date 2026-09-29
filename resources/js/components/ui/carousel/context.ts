import type { InjectionKey, Ref } from "vue";
import type { EmblaCarouselType } from "embla-carousel";

export interface CarouselContext {
  emblaRef: Ref<HTMLElement | undefined>;
  emblaApi: Ref<EmblaCarouselType | undefined>;
  orientation: "horizontal" | "vertical";
  scrollPrev: () => void;
  scrollNext: () => void;
  canScrollPrev: Ref<boolean>;
  canScrollNext: Ref<boolean>;
}

export const CAROUSEL_CONTEXT_KEY: InjectionKey<CarouselContext> = Symbol("CarouselContext");
