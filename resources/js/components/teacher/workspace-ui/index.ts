/**
 * Ported from teacher/workspace-ui.tsx — a small shared UI kit for the teacher
 * workspace, one .tsx module exporting several components. Same folder +
 * barrel-export pattern as elsewhere: `import { Card, Section, ... } from "@/components/teacher/workspace-ui"`.
 */
export { default as Card } from "./Card.vue";
export { default as Section } from "./Section.vue";
export { default as PageHeader } from "./PageHeader.vue";
export { default as EmptyState } from "./EmptyState.vue";
export { default as FilterBar } from "./FilterBar.vue";
export { default as FilterSelect } from "./FilterSelect.vue";
export { default as SearchInput } from "./SearchInput.vue";
export { default as Tabs } from "./Tabs.vue";

export const TONES: Record<string, { bg: string; text: string; ring: string }> = {
  violet: { bg: "bg-violet-50", text: "text-violet-600", ring: "ring-violet-100" },
  blue: { bg: "bg-blue-50", text: "text-blue-600", ring: "ring-blue-100" },
  emerald: { bg: "bg-emerald-50", text: "text-emerald-600", ring: "ring-emerald-100" },
  amber: { bg: "bg-amber-50", text: "text-amber-700", ring: "ring-amber-100" },
};

export type Tone = keyof typeof TONES;
