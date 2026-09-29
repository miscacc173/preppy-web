// Helpers for the personalised student journey (post-onboarding).
import { SUBJECTS as BANK_SUBJECTS, QUESTION_BANK, type Subject } from "@/lib/questionBank";
import { platformRepository } from "@/data/repositories/platformRepository";

export type StudentOnboarding = {
  level: string;
  board: string;
  year?: string;
  subjects: string[];
  weak: string[];
  notSureWeak: boolean;
  confidence: Record<string, "low" | "mid" | "high">;
  targets: Record<string, string>;
  examMonth: string;
  examYear: string;
  completedAt: string;
};

const MONTHS = [
  "January","February","March","April","May","June",
  "July","August","September","October","November","December",
];

/** Laravel account UI model -> legacy plan view model. */
export async function loadOnboardingFromApi(): Promise<StudentOnboarding | null> {
  const account = (await platformRepository.account()).account;
  if (!account.profileId) return null;
  return {
    level: account.course?.level ?? "",
    board: account.examBoard?.name ?? "",
    year: account.schoolYear,
    subjects: account.subjects.map((subject) => subject.name),
    weak: account.preferences.weakSubjects,
    notSureWeak: account.preferences.notSureWeak,
    confidence: account.preferences.confidence,
    targets: account.preferences.targetGrades,
    examMonth: account.preferences.examMonth,
    examYear: account.preferences.examYear,
    completedAt: new Date().toISOString(),
  };
}

/** Pick the subject to assess: first weak → lowest-confidence → first subject. */
export function pickPrioritySubject(o: StudentOnboarding): string | null {
  if (o.weak && o.weak.length > 0) return o.weak[0];
  const low = o.subjects.find((s) => o.confidence?.[s] === "low");
  if (low) return low;
  const mid = o.subjects.find((s) => o.confidence?.[s] === "mid");
  if (mid) return mid;
  return o.subjects?.[0] ?? null;
}

/** Match onboarding subject name to a subject in our question bank. */
export function toBankSubject(name: string | null): Subject | null {
  if (!name) return null;
  const n = name.toLowerCase();
  const hit = BANK_SUBJECTS.find(
    (s) => n === s.toLowerCase() || n.startsWith(s.toLowerCase()) || s.toLowerCase().startsWith(n),
  );
  return hit ?? null;
}

/** Return N questions for a subject; if unavailable, borrow from Mathematics. */
export function pickAssessmentQuestions(subject: Subject | null, n = 6) {
  const src = subject ?? "Mathematics";
  const pool = QUESTION_BANK[src] ?? [];
  return pool.slice(0, n).map((q, i) => ({ ...q, id: `${src}-${i}` }));
}

export function monthsUntil(monthName: string, yearStr: string): number | null {
  const m = MONTHS.indexOf(monthName);
  const y = parseInt(yearStr, 10);
  if (m < 0 || Number.isNaN(y)) return null;
  const now = new Date();
  const target = new Date(y, m, 1);
  const months = (target.getFullYear() - now.getFullYear()) * 12 + (target.getMonth() - now.getMonth());
  return months;
}

export function formatExamPeriod(o: StudentOnboarding): string {
  const parts = [o.examMonth, o.examYear].filter(Boolean);
  return parts.length ? parts.join(" ") : "Not set";
}

export type AssessmentResult = {
  subject: string;
  bankSubject: Subject | null;
  total: number;
  correct: number;
  answers: { qid: string; correct: boolean }[];
  completedAt: string;
};

export function saveAssessment(r: AssessmentResult) {
  try {
    window.localStorage.setItem("aistar:studentAssessment", JSON.stringify(r));
  } catch {
    /* ignore */
  }
}

export function loadAssessment(): AssessmentResult | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem("aistar:studentAssessment");
    return raw ? (JSON.parse(raw) as AssessmentResult) : null;
  } catch {
    return null;
  }
}

/** Rough grade estimate from % — presented as an *estimate*, never a guarantee. */
export function estimateLevel(pct: number, level: string): string {
  const isNumeric = level === "GCSE";
  if (isNumeric) {
    if (pct >= 85) return "Grade 8–9";
    if (pct >= 70) return "Grade 7";
    if (pct >= 55) return "Grade 6";
    if (pct >= 40) return "Grade 5";
    return "Grade 4 or below";
  }
  if (pct >= 85) return "Grade A*";
  if (pct >= 72) return "Grade A";
  if (pct >= 60) return "Grade B";
  if (pct >= 48) return "Grade C";
  if (pct >= 35) return "Grade D";
  return "Grade E or below";
}
