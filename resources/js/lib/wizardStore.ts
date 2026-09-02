import { ref, watch } from "vue";

// ---------- Learn wizard ----------
export type LearnQType = "mcq" | "short" | "mixed";

export const LEARN_QTYPE_LABELS = ["Multiple Choice", "Structured Questions", "Extended Response", "Data Analysis", "Practical Skills"] as const;
export type LearnQTypeLabel = (typeof LEARN_QTYPE_LABELS)[number];

export const LEARN_DIFFICULTIES = ["Easy", "Medium", "Hard", "Mixed"] as const;
export type LearnDifficulty = (typeof LEARN_DIFFICULTIES)[number];

export type AnswerMethod = "keyboard" | "handwritten";
export type QuestionSource = "bank" | "past-papers" | "ai" | "mixed";
export type LearningMode = "guided" | "focused" | "challenge";
export type FeedbackDepth = "instant" | "detailed" | "off";

export interface LearnWizard {
  level: string;
  subject: string;
  topics: string[];
  qtypes: LearnQTypeLabel[];
  difficulty: LearnDifficulty;
  count: number;
  practiceMin: number;
  answerMethod: AnswerMethod;
  questionSource: QuestionSource;
  learningMode: LearningMode;
  timed: boolean;
  feedbackDepth: FeedbackDepth;
  instantExplanation: boolean;
  topic?: string;
  qtype?: LearnQType;
}

export const DEFAULT_LEARN: LearnWizard = {
  level: "",
  subject: "",
  topics: [],
  qtypes: [],
  difficulty: "Medium",
  count: 5,
  practiceMin: 0,
  answerMethod: "keyboard",
  questionSource: "mixed",
  learningMode: "guided",
  timed: false,
  feedbackDepth: "detailed",
  instantExplanation: true,
};

// ---------- Exam wizard ----------
export interface ExamWizard {
  level: string;
  subject: string;
  paperId: string;
  answerMethod: AnswerMethod;
  instantExplanation: boolean;
  feedbackDepth: FeedbackDepth;
}

export const DEFAULT_EXAM: ExamWizard = {
  level: "",
  subject: "",
  paperId: "",
  answerMethod: "keyboard",
  instantExplanation: false,
  feedbackDepth: "detailed",
};

// ---------- Generic persisted store ----------
// Ported from wizardStore.ts's useStored<T> hook. Each key gets its own
// module-level shared ref (same shared-ref pattern as sessionStore.ts /
// subscription.ts from earlier phases) so every component reading a wizard
// stays in sync with every writer, and localStorage is kept up to date via a
// deep watcher.
function readStored<T>(key: string, initial: T): T {
  if (typeof window === "undefined") return initial;
  try {
    const s = window.localStorage.getItem(key);
    if (!s) return initial;
    return { ...initial, ...(JSON.parse(s) as Partial<T>) } as T;
  } catch {
    return initial;
  }
}

const stores = new Map<string, ReturnType<typeof ref>>();

function useStored<T extends object>(key: string, initial: T) {
  if (!stores.has(key)) {
    const value = ref<T>(readStored(key, initial));
    watch(
      value,
      (v) => {
        try {
          window.localStorage.setItem(key, JSON.stringify(v));
        } catch {
          /* ignore */
        }
      },
      { deep: true },
    );
    stores.set(key, value);
  }
  const value = stores.get(key)! as import("vue").Ref<T>;

  function update(patch: Partial<T> | ((prev: T) => T)) {
    value.value = typeof patch === "function" ? (patch as (p: T) => T)(value.value) : { ...value.value, ...patch };
  }
  function reset() {
    value.value = initial;
  }

  return [value, update, reset] as const;
}

export const useLearnWizard = () => useStored<LearnWizard>("aistar:learnWizard", DEFAULT_LEARN);
export const useExamWizard = () => useStored<ExamWizard>("aistar:examWizard", DEFAULT_EXAM);
