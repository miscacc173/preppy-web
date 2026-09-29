import { api } from "@/lib/api";
import { mapTutorFeedback } from "@/data/mappers/tutorFeedbackMapper";
import type { UiTutorQuestionFeedback } from "@/data/models/tutorFeedback";

const query = (sessionType: "learn" | "exam", sessionId: string) => new URLSearchParams({ session_type: sessionType, session_id: sessionId }).toString();

export const tutorFeedbackRepository = {
  async list(sessionType: "learn" | "exam", sessionId: string) {
    return (await api<{ data: unknown[] }>(`/tutor-question-feedback?${query(sessionType, sessionId)}`)).data.map(mapTutorFeedback);
  },
  async save(payload: { session_type: "learn" | "exam"; session_id: string; question_key: string; question_text: string; answer_text: string | null; feedback: string; verdict: string | null; metadata?: Record<string, unknown> }) {
    return mapTutorFeedback((await api<{ data: unknown }>("/tutor-question-feedback", { method: "POST", body: JSON.stringify(payload) })).data);
  },
  remove(id: number) { return api(`/tutor-question-feedback/${id}`, { method: "DELETE" }); },
};
