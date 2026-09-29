import { asNumber, readField } from "@/data/core/readField";
import type { UiAccount, UiSubject, UiSubscription } from "@/data/models/platform";

export interface SubjectCardModel {
  id: number; name: string; progress: number; predicted: string; readiness: number;
  attempted: number; avg: number; last: string;
}

export function mapSubjectCard(subject: UiSubject): SubjectCardModel {
  const source = { ...subject, metadata: subject.metadata };
  return {
    id: subject.id,
    name: subject.name,
    progress: asNumber(readField(source, ["metadata.progress", "metadata.progress_percent"], 0)),
    predicted: String(readField(source, ["metadata.predicted_grade", "metadata.predictedGrade"], "—")),
    readiness: asNumber(readField(source, ["metadata.readiness", "metadata.readiness_score"], 0)),
    attempted: asNumber(readField(source, ["metadata.questions_attempted", "metadata.attempted"], 0)),
    avg: asNumber(readField(source, ["metadata.average_mark", "metadata.average", "metadata.avg"], 0)),
    last: String(readField(source, ["metadata.last_activity", "metadata.lastActivity"], "No activity yet")),
  };
}

export function mapAcademicSetup(account: UiAccount) {
  return [
    { l: "Exam Board", v: account.examBoard?.name || "Not selected" },
    { l: "Level", v: account.course?.name || "Not selected" },
    { l: "Academic Year", v: account.schoolYear || "Not selected" },
  ];
}

export function mapSubscriptionInfo(subscription: UiSubscription | null, subjectCount: number) {
  return [
    { l: "Active Plan", v: subscription?.plan?.name || "No active plan" },
    { l: "Included Subjects", v: String(subjectCount) },
    { l: "Status", v: subscription?.status || "Inactive" },
    { l: "Renewal Date", v: subscription?.periodEnd ? new Date(subscription.periodEnd).toLocaleDateString() : "—" },
  ];
}
