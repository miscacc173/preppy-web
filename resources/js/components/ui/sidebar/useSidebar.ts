import { inject } from "vue";
import { SIDEBAR_CONTEXT_KEY } from "./context";

export function useSidebar() {
  const context = inject(SIDEBAR_CONTEXT_KEY);
  if (!context) {
    throw new Error("useSidebar must be used within a SidebarProvider.");
  }
  return context;
}
