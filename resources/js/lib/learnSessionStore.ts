// Multi-session persistent in-progress store.
// Every Learn / Practice / Exam session is saved independently under its own
// id so students can pause and resume several sessions in parallel across
// different subjects and modes. Starting a new session NEVER overwrites an
// existing unfinished one; only completion, submission, or explicit discard
// removes a record.
//
// Ported ahead of the rest of Phase 5's lib/* stores because ResumeSessionCard
// (Phase 3) needs it directly. Pure localStorage logic is unchanged 1:1; only
// the React `useInProgressSessions` hook became a Vue composable using a ref.

import { onBeforeUnmount, onMounted, ref } from "vue";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";

export interface PersistedQuestion {
  q: string;
  type: "mcq" | "short";
  marks: number;
  opts?: string[];
  correctOpt?: number;
  markScheme: string[];
  hints: string[];
  starAnswer: string;
  visual?: unknown;
}

export interface PersistedAnswer {
  given: string | number | null;
  skipped: boolean;
  marksAwarded: number;
  timeSec: number;
}

export interface InProgressSession {
  id: string;
  kind: "Learn" | "Exam" | "Practice";
  level: string;
  subject: string;
  topics: string[];
  difficulty: string;
  practiceMin: number;
  count: number;
  questions: PersistedQuestion[];
  answers: PersistedAnswer[];
  qIdx: number;
  currentText: string;
  currentOpt: number | null;
  hintsShown: number;
  showSample: boolean;
  sessionSec: number;
  practiceLeft: number;
  startedAt: string;
  updatedAt: string;
}

const KEY = "aistar.inProgressSessions.v2";
const LEGACY_KEY = "aistar.inProgressSession.v1";
const EVENT = "aistar:inprogress-changed";
const syncTimers = new Map<string, ReturnType<typeof setTimeout>>();

type SessionMap = Record<string, InProgressSession>;

function readAll(): SessionMap {
  if (typeof window === "undefined") return {};
  try {
    const raw = localStorage.getItem(KEY);
    if (raw) return JSON.parse(raw) as SessionMap;
    const legacy = localStorage.getItem(LEGACY_KEY);
    if (legacy) {
      const s = JSON.parse(legacy) as InProgressSession;
      if (s?.id) {
        const map: SessionMap = { [s.id]: s };
        localStorage.setItem(KEY, JSON.stringify(map));
        localStorage.removeItem(LEGACY_KEY);
        return map;
      }
      localStorage.removeItem(LEGACY_KEY);
    }
    return {};
  } catch {
    return {};
  }
}

function writeAll(map: SessionMap) {
  if (typeof window === "undefined") return;
  localStorage.setItem(KEY, JSON.stringify(map));
  window.dispatchEvent(new Event(EVENT));
}

export function newSessionId(): string {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID();
  }
  return `s_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
}

export function saveInProgress(session: InProgressSession) {
  const map = readAll();
  map[session.id] = { ...session, updatedAt: new Date().toISOString() };
  writeAll(map);
  const existing = syncTimers.get(session.id);
  if (existing) clearTimeout(existing);
  syncTimers.set(session.id, setTimeout(() => {
    syncTimers.delete(session.id);
    void learningSessionRepository.saveProgress(session.id, {
        session_type: session.kind === "Exam" ? "exam" : "learn",
        level: session.level || null,
        subject: session.subject,
        topic: session.topics.join(", ") || null,
        time_seconds: session.sessionSec,
        started_at: session.startedAt,
        questions: session.questions.map((question, index) => ({ ...question, answer: session.answers[index] ?? null })),
        metadata: { difficulty: session.difficulty, progress_question_index: session.qIdx },
    }).catch(() => undefined);
  }, 1500));
}

export function getInProgressById(id: string): InProgressSession | null {
  return readAll()[id] ?? null;
}

export function getAllInProgress(): InProgressSession[] {
  return Object.values(readAll()).sort(
    (a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime(),
  );
}

export function clearInProgress(id: string) {
  const map = readAll();
  if (map[id]) {
    delete map[id];
    writeAll(map);
  }
}

export function useInProgressSessions() {
  // Start empty so SSR/client hydration match; then hydrate from localStorage
  // on mount so resume-in-progress cards render on first load.
  const list = ref<InProgressSession[]>([]);

  function sync() {
    list.value = getAllInProgress();
  }

  onMounted(() => {
    sync();
    window.addEventListener(EVENT, sync);
    window.addEventListener("storage", sync);
  });
  onBeforeUnmount(() => {
    window.removeEventListener(EVENT, sync);
    window.removeEventListener("storage", sync);
  });

  return list;
}

export function relativeTime(iso: string): string {
  const then = new Date(iso).getTime();
  const now = Date.now();
  const diff = Math.max(0, Math.round((now - then) / 1000));
  if (diff < 60) return "just now";
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) {
    const h = Math.floor(diff / 3600);
    return `${h} hour${h === 1 ? "" : "s"} ago`;
  }
  const d = Math.floor(diff / 86400);
  return `${d} day${d === 1 ? "" : "s"} ago`;
}

export function sessionProgress(s: InProgressSession) {
  const answered = s.answers.filter((a) => a.given !== null && !a.skipped).length;
  const total = s.questions.length || s.count;
  const pct = total ? Math.round((answered / total) * 100) : 0;
  const earned = s.answers.reduce((sum, a) => sum + a.marksAwarded, 0);
  const totalMarks = s.questions.reduce((sum, q) => sum + q.marks, 0);
  return { answered, total, pct, earned, totalMarks };
}
