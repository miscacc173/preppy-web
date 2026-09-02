import { asNumber, readField } from "@/data/core/readField";
import type { UiTutorQuestionFeedback } from "@/data/models/tutorFeedback";

export function mapTutorFeedback(raw: unknown): UiTutorQuestionFeedback {
  return {
    id: asNumber(readField(raw, "id", 0)),
    sessionType: String(readField(raw, ["session_type", "sessionType"], "learn")) as UiTutorQuestionFeedback["sessionType"],
    sessionId: String(readField(raw, ["session_id", "sessionId"], "")),
    questionKey: String(readField(raw, ["question_key", "questionKey"], "")),
    questionText: String(readField(raw, ["question_text", "questionText"], "")),
    answerText: String(readField(raw, ["answer_text", "answerText"], "")),
    feedback: String(readField(raw, "feedback", "")),
    verdict: String(readField(raw, "verdict", "")) as UiTutorQuestionFeedback["verdict"],
    updatedAt: String(readField(raw, ["updated_at", "updatedAt"], "")),
  };
}
