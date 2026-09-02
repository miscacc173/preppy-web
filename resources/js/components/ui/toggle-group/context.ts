import type { InjectionKey } from "vue";
import type { ToggleVariants } from "@/components/ui/toggle";

export interface ToggleGroupContext {
  variant?: ToggleVariants["variant"];
  size?: ToggleVariants["size"];
}

export const TOGGLE_GROUP_CONTEXT_KEY: InjectionKey<ToggleGroupContext> = Symbol("ToggleGroupContext");
