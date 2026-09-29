/**
 * Ported from app/Visuals.tsx — that file exported three components (Bars, Ring,
 * Sparkline) from one .tsx module. Vue SFCs are one-component-per-file, so this
 * is a small folder with the same barrel-export pattern used across the project;
 * import sites are unchanged: `import { Bars, Ring, Sparkline } from "@/components/app/visuals"`.
 */
export { default as Bars } from "./Bars.vue";
export { default as Ring } from "./Ring.vue";
export { default as Sparkline } from "./Sparkline.vue";

export const colorMap = {
  blue: "var(--blue)",
  violet: "var(--violet)",
  mint: "var(--mint)",
  gold: "var(--gold)",
  pink: "var(--pink)",
} as const;

export type VisualColor = keyof typeof colorMap;
export type BarRow = { label: string; value: number; color?: VisualColor };
