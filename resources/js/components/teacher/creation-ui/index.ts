/**
 * Ported from teacher/creation-ui.tsx — same folder + barrel pattern as
 * workspace-ui. `TextInput`/`NumberInput`/`Textarea` become thin wrappers with
 * v-model + $attrs pass-through instead of `React.InputHTMLAttributes` spreads.
 */
export { default as Field } from "./Field.vue";
export { default as TextInput } from "./TextInput.vue";
export { default as NumberInput } from "./NumberInput.vue";
export { default as Select } from "./Select.vue";
export { default as Textarea } from "./Textarea.vue";
export { default as Chip } from "./Chip.vue";
export { default as MultiChips } from "./MultiChips.vue";
export { default as Toggle } from "./Toggle.vue";
export { default as FormCard } from "./FormCard.vue";
export { default as GenerationOverlay } from "./GenerationOverlay.vue";
export { default as StepHeader } from "./StepHeader.vue";

export const inputCx =
  "w-full bg-bg-card border border-border rounded-lg px-3 py-2 text-[13.5px] text-ink outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100 transition";
