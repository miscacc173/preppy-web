import type { UiAccount, UiPlan } from "@/data/models/platform";

export function planMatchesAccount(plan: UiPlan, account: UiAccount): boolean {
  if (plan.scope === "all_subjects") return true;
  if (plan.scope === "institute") return false;
  if (plan.scope === "course") return Boolean(account.course?.id && plan.courseId === account.course.id);

  if (["subject", "multi_subject"].includes(plan.scope)) {
    const selectedSubjectIds = new Set(account.subjects.map((subject) => subject.id));
    return plan.subjectIds.some((subjectId) => selectedSubjectIds.has(subjectId));
  }

  return false;
}
