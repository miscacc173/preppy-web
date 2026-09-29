import type { InjectionKey, Ref } from "vue";

export interface CommandContext {
  search: Ref<string>;
  /** Incremented whenever the visible-item count changes, so CommandEmpty can react. */
  visibleCount: Ref<number>;
}

export const COMMAND_CONTEXT_KEY: InjectionKey<CommandContext> = Symbol("CommandContext");
