/**
 * GradeBadge — the single canonical way to render a grade anywhere in Preppy.
 * Ported 1:1 from GradeBadge.tsx — same grade logic, same size styles.
 */
export { default as GradeBadge } from "./GradeBadge.vue";

export type Grade = "A*" | "A" | "B" | "C" | "D" | "E" | "U";

const GRADE_ORDER: Grade[] = ["A*", "A", "B", "C", "D", "E", "U"];

/** Map any numeric readiness / accuracy (0-100) to a canonical Grade. */
export function gradeFromScore(score: number): Grade {
  if (score >= 90) return "A*";
  if (score >= 80) return "A";
  if (score >= 70) return "B";
  if (score >= 60) return "C";
  if (score >= 50) return "D";
  if (score >= 40) return "E";
  return "U";
}

/** Normalise any incoming grade-ish string to the canonical Grade format. */
export function normalizeGrade(input: string | Grade | null | undefined): Grade {
  if (!input) return "U";
  const s = String(input).trim().toUpperCase().replace(/\s+/g, "");
  if (s === "A*" || s === "ASTAR" || s === "A-STAR" || s === "A★" || s === "*") return "A*";
  const first = s[0] as Grade;
  return GRADE_ORDER.includes(first) ? first : "U";
}

export type GradeBadgeSize = "sm" | "md" | "lg" | "xl";

export const GRADE_SIZE_STYLES: Record<GradeBadgeSize, string> = {
  sm: "text-[10px] px-2 py-0.5",
  md: "text-[11px] px-2 py-0.5",
  lg: "text-[13px] px-2.5 py-1",
  xl: "text-[22px] px-3 py-1",
};
