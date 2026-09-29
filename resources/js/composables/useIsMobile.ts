import { onBeforeUnmount, onMounted, ref } from "vue";

const MOBILE_BREAKPOINT = 768;

export function useIsMobile() {
  const isMobile = ref<boolean | undefined>(undefined);
  let mql: MediaQueryList | undefined;

  function onChange() {
    isMobile.value = window.innerWidth < MOBILE_BREAKPOINT;
  }

  onMounted(() => {
    mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
    mql.addEventListener("change", onChange);
    onChange();
  });

  onBeforeUnmount(() => {
    mql?.removeEventListener("change", onChange);
  });

  return isMobile;
}
