import type { InjectionKey, Ref } from "vue";

export interface SidebarContext {
  state: "expanded" | "collapsed";
  open: Ref<boolean>;
  setOpen: (open: boolean) => void;
  openMobile: Ref<boolean>;
  setOpenMobile: (open: boolean) => void;
  isMobile: Ref<boolean | undefined>;
  toggleSidebar: () => void;
}

export const SIDEBAR_CONTEXT_KEY: InjectionKey<SidebarContext> = Symbol("SidebarContext");
