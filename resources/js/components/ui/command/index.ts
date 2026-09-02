/**
 * DEVIATION FROM SOURCE: the React version wraps the `cmdk` package (Command
 * Primitive), which has no actively-maintained, verified Vue port as of this
 * conversion. Rather than assume an unverified npm package exists, this is a
 * self-built minimal command-palette built on reka-ui's ListboxRoot/ListboxItem
 * (keyboard nav + a11y) plus a simple provide/inject search-filter context that
 * mirrors cmdk's behavior (CommandInput's v-model drives which CommandItems are
 * visible). It covers the standard case (search + filtered list + keyboard
 * select) but doesn't replicate cmdk's fuzzy-match scoring — matching here is a
 * plain case-insensitive substring test against each item's `value`/text. If
 * you rely on cmdk's fuzzy ranking specifically, flag it and we can build that
 * matcher out further.
 */
export { default as Command } from "./Command.vue";
export { default as CommandDialog } from "./CommandDialog.vue";
export { default as CommandInput } from "./CommandInput.vue";
export { default as CommandList } from "./CommandList.vue";
export { default as CommandEmpty } from "./CommandEmpty.vue";
export { default as CommandGroup } from "./CommandGroup.vue";
export { default as CommandSeparator } from "./CommandSeparator.vue";
export { default as CommandItem } from "./CommandItem.vue";
export { default as CommandShortcut } from "./CommandShortcut.vue";
