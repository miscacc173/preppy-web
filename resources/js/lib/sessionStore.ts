// Shared in-memory + localStorage store so Session Review and Full Analytics
// present identical numbers from a single source of truth.
//
// Pulled forward from Phase 5 since MotivationWidgets (Phase 3) needs it
// directly. Pure localStorage logic ported 1:1; only the React
// `useSessions` hook became a Vue composable.

import { onBeforeUnmount, onMounted, ref } from "vue";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";

export interface SessionQuestionRecord {
  q: string;
  type: "mcq" | "short";
  marks: number;
  marksAwarded: number;
  correct: boolean;
  skipped: boolean;
  given: string | null; // human-readable answer text
  starAnswer: string;
  timeSec: number;
}

export interface SessionRecord {
  id: string;
  date: string; // ISO date
  dateLabel: string; // "Today", "Yesterday", "May 28"
  level: string;
  subject: string;
  topic: string;
  type: "Learn" | "Exam";
  earned: number;
  totalMarks: number;
  accuracy: number; // 0-100
  grade: string; // A*, A, B...
  timeSec: number;
  avgPerQ: number;
  questions: SessionQuestionRecord[];
  metadata?: Record<string, unknown>;
}

const KEY = "aistar.sessions.v1";

function read(): SessionRecord[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = localStorage.getItem(KEY);
    return raw ? (JSON.parse(raw) as SessionRecord[]) : [];
  } catch {
    return [];
  }
}

function write(list: SessionRecord[]) {
  if (typeof window === "undefined") return;
  localStorage.setItem(KEY, JSON.stringify(list));
  window.dispatchEvent(new Event("aistar:sessions-changed"));
}

export function gradeFor(accuracy: number) {
  return accuracy >= 90 ? "A*" : accuracy >= 80 ? "A" : accuracy >= 70 ? "B" : accuracy >= 60 ? "C" : accuracy >= 50 ? "D" : "U";
}

export function dateLabelFor(d: Date) {
  const today = new Date();
  const y = new Date();
  y.setDate(today.getDate() - 1);
  const sameDay = (a: Date, b: Date) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
  if (sameDay(d, today)) return "Today";
  if (sameDay(d, y)) return "Yesterday";
  return d.toLocaleDateString(undefined, { month: "short", day: "numeric" });
}

export function saveSession(rec: Omit<SessionRecord, "id" | "date" | "dateLabel"> & { id?: string }) {
  const now = new Date();
  const { id, ...data } = rec;
  const record: SessionRecord = {
    ...data,
    id: id || (typeof crypto !== "undefined" && "randomUUID" in crypto ? crypto.randomUUID() : `${now.getTime()}`),
    date: now.toISOString(),
    dateLabel: dateLabelFor(now),
  };
  const list = [record, ...read()].slice(0, 50);
  write(list);
  void learningSessionRepository.save(record).catch(() => undefined);
  return record;
}

export function getSessions(): SessionRecord[] {
  return read();
}

export function getLatestSession(): SessionRecord | null {
  return read()[0] ?? null;
}

export function useSessions() {
  // Initialize as empty so SSR + client hydration agree. Real data is read
  // on mount so a completed session always shows up on the Results page even
  // on first render.
  const list = ref<SessionRecord[]>([]);

  function sync() {
    list.value = read();
  }

  onMounted(() => {
    sync();
    void learningSessionRepository.list().then((remote) => {
      if (remote.length) write(remote);
    }).catch(() => undefined);
    window.addEventListener("aistar:sessions-changed", sync);
    window.addEventListener("storage", sync);
  });
  onBeforeUnmount(() => {
    window.removeEventListener("aistar:sessions-changed", sync);
    window.removeEventListener("storage", sync);
  });

  return list;
}
