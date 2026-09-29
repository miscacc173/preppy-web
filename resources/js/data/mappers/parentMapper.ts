import { asNumber, asStringArray, readField } from "@/data/core/readField";
import type { UiInviteDetails, UiParentStudent } from "@/data/models/parent";

export function mapParentStudent(raw: unknown): UiParentStudent {
  const student = readField(raw, ["student"], {});
  const permissions = readField(raw, ["permissions"], {});
  const performance = readField<unknown>(raw, ["performance"], null);
  const subscription = readField<unknown>(raw, ["subscription"], null);
  return {
    id: asNumber(readField(raw, ["id"], 0)), status: String(readField(raw, ["status"], "pending")), relationship: String(readField(raw, ["relationship"], "Parent")),
    permissions: { performance: Boolean(readField(permissions, ["performance"], false)), subscription: Boolean(readField(permissions, ["subscription"], false)) },
    student: { id: asNumber(readField(student, ["id"], 0)), name: String(readField(student, ["name"], "Student")), schoolYear: String(readField(student, ["school_year"], "")), schoolName: String(readField(student, ["school_name"], "")), course: String(readField(student, ["course"], "")), examBoard: String(readField(student, ["exam_board"], "")), institute: String(readField(student, ["institute"], "")), subjects: asStringArray(readField(student, ["subjects"], [])) },
    performance: performance ? {
      overallReadiness: readField<number | null>(performance, ["overall_readiness"], null),
      dailyProgress: (readField<unknown[]>(performance, ["daily_progress"], [])).map((item) => ({ date: String(readField(item, "date", "")), sessions: asNumber(readField(item, "sessions", 0)), minutes: asNumber(readField(item, "minutes", 0)), averageScore: asNumber(readField(item, ["average_score"], 0)) })),
      activeSessions: mapProgressSessions(readField(performance, ["active_sessions"], [])),
      completedSessions: mapProgressSessions(readField(performance, ["completed_sessions"], [])),
      subjectReadiness: (readField<unknown[]>(performance, ["subject_readiness"], [])).map((item) => ({
        subject: String(readField(item, "subject", "")),
        readiness: asNumber(readField(item, "readiness", 0)),
        hasActivity: Boolean(readField(item, ["has_activity"], false)),
        earned: asNumber(readField(item, "earned", 0)),
        totalMarks: asNumber(readField(item, ["total_marks"], 0)),
        completedSessions: asNumber(readField(item, ["completed_sessions"], 0)),
        activeSessions: asNumber(readField(item, ["active_sessions"], 0)),
        averageScore: nullableNumber(readField(item, ["average_score"], null)),
        bestScore: nullableNumber(readField(item, ["best_score"], null)),
        latestScore: nullableNumber(readField(item, ["latest_score"], null)),
        totalMinutes: asNumber(readField(item, ["total_minutes"], 0)),
        lastSessionAt: String(readField(item, ["last_session_at"], "")),
        recentSessions: mapProgressSessions(readField(item, ["recent_sessions"], [])),
      })),
    } : null,
    subscription: subscription ? { status: String(readField(subscription, ["status"], "")), plan: String(readField(subscription, ["plan"], "")), periodEnd: String(readField(subscription, ["period_end"], "")) } : null,
  };
}

function mapProgressSessions(value: unknown) {
  return (Array.isArray(value) ? value : []).map((item) => ({
    id: String(readField(item, "id", "")),
    type: String(readField(item, "type", "")),
    subject: String(readField(item, "subject", "")),
    topic: String(readField(item, "topic", "")),
    status: String(readField(item, "status", "")),
    score: asNumber(readField(item, "score", 0)),
    earned: asNumber(readField(item, "earned", 0)),
    totalMarks: asNumber(readField(item, ["total_marks"], 0)),
    grade: String(readField(item, "grade", "")),
    timeSeconds: asNumber(readField(item, ["time_seconds"], 0)),
    averageSecondsPerQuestion: asNumber(readField(item, ["average_seconds_per_question"], 0)),
    questionCount: asNumber(readField(item, ["question_count"], 0)),
    answeredCount: asNumber(readField(item, ["answered_count"], 0)),
    correctCount: asNumber(readField(item, ["correct_count"], 0)),
    startedAt: String(readField(item, ["started_at"], "")),
    completedAt: String(readField(item, ["completed_at"], "")),
  }));
}

function nullableNumber(value: unknown): number | null {
  return value === null || value === undefined || value === "" ? null : asNumber(value);
}

export function mapInvite(raw: unknown, type: "parent" | "institute" | "educator"): UiInviteDetails {
  return { email: String(readField(raw, ["email"], "")), name: String(readField(raw, ["parent_name"], "")), context: String(readField(raw, type === "parent" ? ["student_name"] : ["institute"], "")), role: String(readField(raw, type === "parent" ? ["relationship"] : ["role"], "")), expiresAt: String(readField(raw, ["expires_at"], "")), existingAccount: Boolean(readField(raw, ["existing_account"], false)) };
}
