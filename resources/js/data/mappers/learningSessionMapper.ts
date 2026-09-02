import { asNumber, asStringArray, readField } from "@/data/core/readField";
import type { UiLearningSession, UiTutorReviewItem, UiTutorStudent } from "@/data/models/learningSession";
import type { SessionQuestionRecord } from "@/lib/sessionStore";

function question(raw: unknown): SessionQuestionRecord {
  return {
    q: String(readField(raw, ["q"], "")),
    type: readField(raw, ["type"], "short"),
    marks: asNumber(readField(raw, ["marks"], 0)),
    marksAwarded: asNumber(readField(raw, ["marks_awarded", "marksAwarded"], 0)),
    correct: Boolean(readField(raw, ["correct"], false)),
    skipped: Boolean(readField(raw, ["skipped"], false)),
    given: readField<string | null>(raw, ["given"], null),
    starAnswer: String(readField(raw, ["star_answer", "starAnswer"], "")),
    timeSec: asNumber(readField(raw, ["time_seconds", "timeSec"], 0)),
  };
}

export function mapLearningSession(raw: unknown): UiLearningSession {
  const date = String(readField(raw, ["completed_at", "date"], new Date().toISOString()));
  const user = readField(raw, ["user"], {});
  const questions = readField<unknown[]>(raw, ["questions"], []);
  return {
    id: String(readField(raw, ["session_uid", "id"], "")),
    userId: asNumber(readField(raw, ["user_id", "userId"], 0)),
    userName: String(readField(user, ["name"], "")),
    date,
    dateLabel: new Date(date).toLocaleDateString(undefined, { month: "short", day: "numeric" }),
    level: String(readField(raw, ["level"], "")),
    subject: String(readField(raw, ["subject"], "")),
    topic: String(readField(raw, ["topic"], "")),
    type: String(readField(raw, ["session_type", "type"], "learn")).toLowerCase() === "exam" ? "Exam" : "Learn",
    earned: asNumber(readField(raw, ["earned"], 0)),
    totalMarks: asNumber(readField(raw, ["total_marks", "totalMarks"], 0)),
    accuracy: asNumber(readField(raw, ["accuracy"], 0)),
    grade: String(readField(raw, ["grade"], "")),
    timeSec: asNumber(readField(raw, ["time_seconds", "timeSec"], 0)),
    avgPerQ: asNumber(readField(raw, ["average_seconds_per_question", "avgPerQ"], 0)),
    questions: questions.map(question),
    metadata: readField<Record<string, unknown>>(raw, ["metadata"], {}),
  };
}

export function mapTutorStudent(raw: unknown): UiTutorStudent {
  const user = readField(raw, ["user"], {});
  const course = readField(raw, ["course"], {});
  const board = readField(raw, ["exam_board", "examBoard"], {});
  const subjects = readField<unknown[]>(raw, ["subjects"], []);
  const progress = readField(raw, ["progress"], {});
  const sessionList = (key: string) => readField<unknown[]>(progress, [key], []).map((item) => ({ id: String(readField(item, "id", "")), type: String(readField(item, "type", "")), subject: String(readField(item, "subject", "")), topic: String(readField(item, "topic", "")), score: asNumber(readField(item, "score", 0)), timeSeconds: asNumber(readField(item, ["time_seconds"], 0)) }));
  return {
    userId: asNumber(readField(raw, ["user_id", "userId"], 0)),
    canManageParents: Boolean(readField(raw, ["can_manage_parents", "canManageParents"], false)),
    name: String(readField(user, ["name"], "Student")),
    email: String(readField(user, ["email"], "")),
    course: String(readField(course, ["name"], "")),
    examBoard: String(readField(board, ["name"], "")),
    subjects: asStringArray(subjects.map((item) => readField(item, ["name"], ""))),
    progress: {
      overallReadiness: readField<number | null>(progress, ["overall_readiness"], null),
      activeSessions: sessionList("active_sessions"), completedSessions: sessionList("completed_sessions"),
      subjectReadiness: readField<unknown[]>(progress, ["subject_readiness"], []).map((item) => ({ subject: String(readField(item, "subject", "")), readiness: asNumber(readField(item, "readiness", 0)), completedSessions: asNumber(readField(item, ["completed_sessions"], 0)) })),
      dailyProgress: readField<unknown[]>(progress, ["daily_progress"], []).map((item) => ({ date: String(readField(item, "date", "")), sessions: asNumber(readField(item, "sessions", 0)), minutes: asNumber(readField(item, "minutes", 0)), averageScore: asNumber(readField(item, ["average_score"], 0)) })),
    },
  };
}

export function mapTutorReviewItem(raw: unknown): UiTutorReviewItem {
  return {
    sessionId: String(readField(raw, ["session_id"], "")),
    sessionType: readField(raw, ["session_type"], "learn"),
    questionKey: String(readField(raw, ["question_key"], "")),
    subject: String(readField(raw, ["subject"], "")),
    topic: String(readField(raw, ["topic"], "")),
    questionText: String(readField(raw, ["question_text"], "")),
    submittedAnswer: String(readField(raw, ["submitted_answer"], "")),
    aiAnswer: String(readField(raw, ["ai_answer"], "")),
    marksAwarded: asNumber(readField(raw, ["marks_awarded"], 0)),
    marks: asNumber(readField(raw, ["marks"], 0)),
    completedAt: String(readField(raw, ["completed_at"], "")),
  };
}
