import type { UiCalendarEvent, UiStudentGoals, UiStudentNotification } from "@/data/models/studentWorkspace";

const list = (value: unknown): unknown[] => Array.isArray(value) ? value : [];
const object = (value: unknown): Record<string, unknown> => value && typeof value === "object" ? value as Record<string, unknown> : {};
const text = (value: unknown) => typeof value === "string" ? value : "";

export function mapCalendar(value: unknown): UiCalendarEvent[] {
  return list(value).map((item) => {
    const row = object(item);
    const type = ["exam", "study", "deadline"].includes(text(row.type)) ? text(row.type) as UiCalendarEvent["type"] : "study";
    return { id: text(row.id), title: text(row.title), date: text(row.date), time: text(row.time), type };
  }).filter((item) => item.id && item.title && item.date);
}

export function mapGoals(value: unknown): UiStudentGoals {
  const row = object(value);
  return {
    weeklyHours: Number(row.weeklyHours || 0),
    examDate: text(row.examDate),
    subjectGoals: list(row.subjectGoals).map((item) => {
      const goal = object(item);
      return { subjectId: Number(goal.subjectId || 0), subject: text(goal.subject), targetGrade: text(goal.targetGrade) };
    }).filter((item) => item.subjectId && item.subject),
  };
}

export function mapNotifications(value: unknown): UiStudentNotification[] {
  return list(value).map((item) => {
    const row = object(item);
    const category = ["study", "exams", "account"].includes(text(row.category)) ? text(row.category) as UiStudentNotification["category"] : "account";
    return { id: text(row.id), title: text(row.title), body: text(row.body), createdAt: text(row.createdAt), read: Boolean(row.read), category, to: text(row.to) || undefined };
  }).filter((item) => item.id && item.title);
}
