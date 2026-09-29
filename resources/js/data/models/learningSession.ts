import type { SessionRecord } from "@/lib/sessionStore";

export interface UiTutorStudent {
  userId: number;
  canManageParents: boolean;
  name: string;
  email: string;
  course: string;
  examBoard: string;
  subjects: string[];
  progress: {
    overallReadiness: number | null;
    activeSessions: Array<{ id: string; type: string; subject: string; topic: string; score: number; timeSeconds: number }>;
    completedSessions: Array<{ id: string; type: string; subject: string; topic: string; score: number; timeSeconds: number }>;
    subjectReadiness: Array<{ subject: string; readiness: number; completedSessions: number }>;
    dailyProgress: Array<{ date: string; sessions: number; minutes: number; averageScore: number }>;
  };
}

export interface UiLearningSession extends SessionRecord {
  userId: number;
  userName: string;
}

export interface UiTutorReviewItem {
  sessionId: string;
  sessionType: "learn" | "exam";
  questionKey: string;
  subject: string;
  topic: string;
  questionText: string;
  submittedAnswer: string;
  aiAnswer: string;
  marksAwarded: number;
  marks: number;
  completedAt: string;
}
