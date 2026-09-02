export interface UiCalendarEvent {
  id: string;
  title: string;
  date: string;
  time: string;
  type: "exam" | "study" | "deadline";
}

export interface UiSubjectGoal {
  subjectId: number;
  subject: string;
  targetGrade: string;
}

export interface UiStudentGoals {
  weeklyHours: number;
  examDate: string;
  subjectGoals: UiSubjectGoal[];
}

export interface UiStudentNotification {
  id: string;
  title: string;
  body: string;
  createdAt: string;
  read: boolean;
  category: "study" | "exams" | "account";
  to?: string;
}
