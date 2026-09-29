/**
 * Ported from form.tsx: `react-hook-form` -> `vee-validate` (the direct Vue
 * equivalent — same field-level validation model). `useId()` is Vue 3.5's
 * native composable (React's `useId` equivalent), so id generation matches
 * the source 1:1 rather than needing a shim.
 */
export { Form } from "vee-validate";
export { default as FormField } from "./FormField.vue";
export { default as FormItem } from "./FormItem.vue";
export { default as FormLabel } from "./FormLabel.vue";
export { default as FormControl } from "./FormControl.vue";
export { default as FormDescription } from "./FormDescription.vue";
export { default as FormMessage } from "./FormMessage.vue";
export { useFormField } from "./useFormField";
