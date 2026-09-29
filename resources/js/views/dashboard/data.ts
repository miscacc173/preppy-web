import type { SessionRecord } from "@/lib/sessionStore";
import { API_FIELD_MAP as F } from "@/data/config/apiFieldMap";
import { asNumber, asStringArray, readField } from "@/data/core/readField";
import type { UiSubject } from "@/data/models/platform";

export function gradeFromReadiness(r: number): "A*" | "A" | "B" | "C" | "D" {
  if (r >= 90) return "A*";
  if (r >= 80) return "A";
  if (r >= 70) return "B";
  if (r >= 55) return "C";
  return "D";
}

export interface SubjectStat {
  name: string; paper: string; readiness: number; grade: string; weak: string; strong: string;
  trend: string; color: "mint" | "gold"; live: boolean; confidence: string; predicted: string;
  examIn: string; examDate: string; strengths: string[]; weaknesses: string[]; missing: string[];
  suggested: string[]; recent: { s: string; score: number; g: string; st: string }[];
  recommendations: string[]; trendSeries: number[];
}

const text = (value: unknown, fallback = "—") => String(value ?? fallback) || fallback;
const strings = (value: unknown) => asStringArray(value);

/** API subject metadata -> exact model consumed by Dashboard.vue. */
export function mapSubjectStat(subject: UiSubject): SubjectStat {
  const source = { ...subject, metadata: subject.metadata };
  const readiness = asNumber(readField(source, F.subjectStats.readiness, 0));
  const predicted = text(readField(source, F.subjectStats.predictedGrade, gradeFromReadiness(readiness)));
  const recentRaw = readField<unknown[]>(source, F.subjectStats.recent, []);
  const trendRaw = readField<unknown[]>(source, F.subjectStats.trendSeries, []);
  return {
    name: subject.name,
    paper: text(readField(source, F.subjectStats.paper, "Paper not assigned")),
    readiness,
    grade: predicted,
    weak: text(readField(source, F.subjectStats.weak, "Not enough data")),
    strong: text(readField(source, F.subjectStats.strong, "Not enough data")),
    trend: text(readField(source, F.subjectStats.trend, "0"), "0"),
    color: readiness >= 70 ? "mint" : "gold",
    live: Boolean(readField(source, ["metadata.live", "live"], false)),
    confidence: text(readField(source, F.subjectStats.confidence, "0%"), "0%"),
    predicted,
    examIn: text(readField(source, F.subjectStats.examIn, "—")),
    examDate: text(readField(source, F.subjectStats.examDate, "Not scheduled")),
    strengths: strings(readField(source, F.subjectStats.strengths, [])),
    weaknesses: strings(readField(source, F.subjectStats.weaknesses, [])),
    missing: strings(readField(source, F.subjectStats.missing, [])),
    suggested: strings(readField(source, F.subjectStats.suggested, [])),
    recent: Array.isArray(recentRaw) ? recentRaw.map((item) => {
      const row = item && typeof item === "object" ? item as Record<string, unknown> : {};
      return { s: text(row.session ?? row.s, "Session"), score: asNumber(row.score), g: text(row.grade ?? row.g), st: text(row.status ?? row.st, "Complete") };
    }) : [],
    recommendations: strings(readField(source, F.subjectStats.recommendations, [])),
    trendSeries: Array.isArray(trendRaw) ? trendRaw.map((item) => asNumber(item)) : [],
  };
}

export const TREND_COLORS = ["#22c1d6", "#3aa9e0", "#5b8def", "#7b6cf0", "#9b87f5", "#b07bd9", "#c25fb3", "#e85d9c", "#ef6f8c", "#f08a73", "#e8a85a", "#22c1d6"];

export function applyDynamicStats(base: SubjectStat, sessions: SessionRecord[]): SubjectStat {
  const subjectSessions = sessions.filter((s) => s.subject === base.name).slice().sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
  if (!subjectSessions.length) return base;
  const recent = subjectSessions.slice(-8).reverse();
  let weight = 0;
  let accuracy = 0;
  recent.forEach((session, index) => { const w = Math.pow(0.75, index); weight += w; accuracy += w * session.accuracy; });
  const readiness = Math.round(accuracy / weight);
  const grade = gradeFromReadiness(readiness);
  return { ...base, readiness, grade, predicted: grade, confidence: `${Math.min(95, 55 + subjectSessions.length * 5)}%`, color: readiness >= 70 ? "mint" : "gold" };
}

export function buildOverall(subjects: SubjectStat[]): SubjectStat {
  const n = subjects.length;
  const readiness = n ? Math.round(subjects.reduce((sum, subject) => sum + subject.readiness, 0) / n) : 0;
  const byReadiness = [...subjects].sort((a, b) => a.readiness - b.readiness);
  const unique = (values: string[]) => Array.from(new Set(values.filter(Boolean)));
  return {
    name: "Overall", paper: "All subscribed subjects", readiness, grade: gradeFromReadiness(readiness),
    weak: byReadiness[0]?.weak ?? "Not enough data", strong: byReadiness.at(-1)?.strong ?? "Not enough data",
    trend: "0", color: readiness >= 70 ? "mint" : "gold", live: subjects.some((s) => s.live),
    confidence: n ? `${Math.min(100, Math.round(70 + readiness / 5))}%` : "0%", predicted: gradeFromReadiness(readiness),
    examIn: subjects.find((s) => s.examIn !== "—")?.examIn ?? "—", examDate: subjects.find((s) => s.examDate !== "Not scheduled")?.examDate ?? "Not scheduled",
    strengths: unique(subjects.flatMap((s) => s.strengths)), weaknesses: unique(subjects.flatMap((s) => s.weaknesses)),
    missing: unique(subjects.flatMap((s) => s.missing)), suggested: unique(subjects.flatMap((s) => s.suggested)),
    recent: subjects.flatMap((s) => s.recent).slice(0, 5), recommendations: unique(subjects.flatMap((s) => s.recommendations)),
    trendSeries: Array.from({ length: 12 }, (_, i) => n ? Math.round(subjects.reduce((sum, s) => sum + (s.trendSeries[i] ?? s.readiness), 0) / n) : 0),
  };
}
