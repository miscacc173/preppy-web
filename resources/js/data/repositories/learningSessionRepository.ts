import { api } from "@/lib/api";
import { mapLearningSession, mapTutorReviewItem, mapTutorStudent } from "@/data/mappers/learningSessionMapper";
import type { SessionRecord } from "@/lib/sessionStore";

function payload(session: SessionRecord) {
  return {
    session_uid: session.id,
    session_type: session.type.toLowerCase(),
    level: session.level || null,
    subject: session.subject,
    topic: session.topic || null,
    earned: session.earned,
    total_marks: session.totalMarks,
    accuracy: session.accuracy,
    grade: session.grade || null,
    time_seconds: session.timeSec,
    average_seconds_per_question: session.avgPerQ,
    completed_at: session.date,
    questions: session.questions.map((item) => ({ q: item.q, type: item.type, marks: item.marks, marks_awarded: item.marksAwarded, correct: item.correct, skipped: item.skipped, given: item.given, star_answer: item.starAnswer, time_seconds: item.timeSec })),
    metadata: session.metadata ?? {},
  };
}

export const learningSessionRepository = {
  async list(userId?: number) {
    const suffix = userId ? `?user_id=${userId}` : "";
    return (await api<{ data: unknown[] }>(`/learning-sessions${suffix}`)).data.map(mapLearningSession);
  },
  async save(session: SessionRecord) {
    return mapLearningSession((await api<{ data: unknown }>("/learning-sessions", { method: "POST", body: JSON.stringify(payload(session)) })).data);
  },
  saveProgress(sessionUid: string, progress: Record<string, unknown>) {
    return api(`/learning-sessions/${encodeURIComponent(sessionUid)}/progress`, { method: "PUT", body: JSON.stringify(progress) });
  },
  async teacherStudents() {
    return (await api<{ data: unknown[] }>("/teacher/students")).data.map(mapTutorStudent);
  },
  async tutorReviewQueue() {
    return (await api<{ data: unknown[] }>("/tutor/review-queue")).data.map(mapTutorReviewItem);
  },
};
