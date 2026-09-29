import { cva } from "class-variance-authority";

export { default as Label } from "./Label.vue";

export const labelVariants = cva(
  "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
);
