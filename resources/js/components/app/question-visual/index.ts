// Lightweight, self-contained SVG diagrams so questions always render a real
// visual (no external asset pipeline). Each kind is a hand-tuned mini-figure
// covering the common exam diagram categories. Ported 1:1 from QuestionVisual.tsx,
// split one component per file (13 diagram renderers + Frame + the dispatcher +
// the top-level QuestionVisual with its lightbox) — same barrel pattern as
// elsewhere: `import { QuestionVisual } from "@/components/app/question-visual"`.
export { default as QuestionVisual } from "./QuestionVisual.vue";

export type VisualKind =
  | "physics-beam"
  | "physics-iv-curve"
  | "physics-circuit"
  | "physics-projectile"
  | "chemistry-structure"
  | "chemistry-apparatus"
  | "biology-cell"
  | "biology-foodweb"
  | "maths-graph"
  | "maths-triangle"
  | "geography-map"
  | "table"
  | "formula";

export interface QuestionVisualSpec {
  kind: VisualKind;
  caption?: string;
  /** Optional rows for kind = "table" */
  rows?: string[][];
  /** Optional formula latex-ish text for kind = "formula" */
  formula?: string;
}

export const STROKE = "var(--ink)";
export const ACCENT = "var(--violet)";
export const MUTED = "var(--ink3)";
