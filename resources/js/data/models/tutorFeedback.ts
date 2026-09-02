export interface UiTutorQuestionFeedback {
  id: number;
  sessionType: "learn" | "exam";
  sessionId: string;
  questionKey: string;
  questionText: string;
  answerText: string;
  feedback: string;
  verdict: "correct" | "partially_correct" | "incorrect" | "needs_review" | "";
  updatedAt: string;
}
