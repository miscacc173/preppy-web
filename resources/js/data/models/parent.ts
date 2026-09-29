export interface UiParentProgressSession {
  id: string;
  type: string;
  subject: string;
  topic: string;
  status: string;
  score: number;
  earned: number;
  totalMarks: number;
  grade: string;
  timeSeconds: number;
  averageSecondsPerQuestion: number;
  questionCount: number;
  answeredCount: number;
  correctCount: number;
  startedAt: string;
  completedAt: string;
}

export interface UiParentSubjectProgress {
  subject: string;
  readiness: number;
  hasActivity: boolean;
  earned: number;
  totalMarks: number;
  completedSessions: number;
  activeSessions: number;
  averageScore: number | null;
  bestScore: number | null;
  latestScore: number | null;
  totalMinutes: number;
  lastSessionAt: string;
  recentSessions: UiParentProgressSession[];
}

export interface UiParentStudent {
  id: number;
  status: string;
  relationship: string;
  permissions: { performance: boolean; subscription: boolean };
  student: { id: number; name: string; schoolYear: string; schoolName: string; course: string; examBoard: string; institute: string; subjects: string[] };
  performance: {
    overallReadiness: number | null;
    dailyProgress: Array<{ date: string; sessions: number; minutes: number; averageScore: number }>;
    activeSessions: UiParentProgressSession[];
    completedSessions: UiParentProgressSession[];
    subjectReadiness: UiParentSubjectProgress[];
  } | null;
  subscription: { status: string; plan: string; periodEnd: string } | null;
}

export interface UiInviteDetails { email: string; name: string; context: string; role: string; expiresAt: string; existingAccount: boolean }
export interface UiInviteActivation { name?: string; password?: string; password_confirmation?: string; current_password?: string }
