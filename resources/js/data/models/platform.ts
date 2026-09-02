import type { Confidence, StudyLevel } from "@/views/student/data";

export type CatalogStatus = "active" | "coming_soon" | string;
export interface UiEntity { id: number; name: string; code: string; status: CatalogStatus }
export interface UiCourse extends UiEntity { level: StudyLevel | ""; rawLevel: string; examBoardId: number | null; boardIds: number[]; subjectIds: number[] }
export interface UiSubject extends UiEntity { metadata: Record<string, unknown> }
export interface UiTopic extends UiEntity { subjectId: number; displayOrder: number; metadata: Record<string, unknown> }
export interface UiPlan extends UiEntity {
  scope: string;
  courseId: number | null;
  subjectIds: number[];
  priceMinor: number;
  currency: string;
  billingInterval: string;
  trialDays: number;
  features: string[];
  popular: boolean;
}
export interface UiCatalog { examBoards: UiEntity[]; courses: UiCourse[]; subjects: UiSubject[]; topics: UiTopic[]; plans: UiPlan[]; developmentPaymentBypass?: boolean; mockPaymentsEnabled?: boolean }
export interface UiStudentPreferences {
  weakSubjects: string[];
  notSureWeak: boolean;
  confidence: Record<string, Confidence>;
  targetGrades: Record<string, string>;
  examMonth: string;
  examYear: string;
  topicIds: number[];
  goal: string;
  primarySubject: string;
  targetGrade: string;
  weeklyHours: number;
  examDate: string;
  learningStyle: string;
  difficulty: string;
  questionTypes: Record<string, boolean>;
  feedback: Record<string, boolean>;
  sessionDefaults: { learnLen: number; examLen: number; qCount: number };
  coach: string;
  focus: string;
}
export interface UiAccount {
  id: number;
  name: string;
  email: string;
  profileId: number | null;
  schoolYear: string;
  schoolName: string;
  course: UiCourse | null;
  examBoard: UiEntity | null;
  subjects: UiSubject[];
  preferences: UiStudentPreferences;
}
export interface UiNotificationPreference { type: string; channel: "email" | "push" | "sms" | "in_app"; enabled: boolean; schedule: Record<string, unknown> }
export interface UiSubscription { id: number; planId: number; status: string; periodEnd: string; cancelAtPeriodEnd: boolean; plan: UiPlan | null }
export interface UiAccountBundle { account: UiAccount; notifications: UiNotificationPreference[] }
