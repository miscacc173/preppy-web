import type { UiAccount, UiPlan } from "@/data/models/platform";

export function planMatchesAccount(plan: UiPlan, account: UiAccount): boolean {
  const courseId = account.course?.id ?? null;
  if (!courseId) return false;

  const courseGroup = plan.courseSubjects.find((group) => group.courseId === courseId);
  if (courseGroup) {
    const selectedSubjectIds = new Set(account.subjects.map((subject) => subject.id));
    return courseGroup.subjectIds.some((subjectId) => selectedSubjectIds.has(subjectId));
  }

  // Legacy fallback for existing plans until they are edited into the new
  // course-first structure.
  if (plan.scope === "all_subjects") return true;
  if (plan.scope === "institute") return Boolean(account.instituteId);
  if (plan.scope === "course") return Boolean(plan.courseId && plan.courseId === courseId);
  if (plan.courseId && plan.courseId !== courseId) return false;
  const selectedSubjectIds = new Set(account.subjects.map((subject) => subject.id));
  return plan.subjectIds.some((subjectId) => selectedSubjectIds.has(subjectId));
}
