import { ref } from "vue";
import { platformRepository } from "@/data/repositories/platformRepository";
import { currentUser } from "@/lib/api";
import { listClasses, readOnboarding } from "@/lib/teacherStore";

/**
 * Compatibility view model used by the existing exam/learn screens. Its data
 * now comes from Laravel account data; no default subjects are injected.
 */
export interface Subscription {
  examBoard: string;
  level: string;
  subjects: string[];
  topicsBySubject: Record<string, string[]>;
}

const subscription = ref<Subscription>({ examBoard: "", level: "", subjects: [], topicsBySubject: {} });
let request: Promise<void> | null = null;

export function hydrateAcademicSubscription() {
  if (!request) {
    request = Promise.all([platformRepository.account(), platformRepository.catalog()]).then(async ([{ account }, catalog]) => {
      const teacher = currentUser()?.roles?.some((role) => role.slug === "teacher") ?? false;
      const tutor = currentUser()?.roles?.some((role) => role.slug === "tutor") ?? false;
      if (tutor) {
        const access = await platformRepository.tutorAcademicAccess();
        const allowedIds = new Set(access.subject_ids.map(Number));
        const selected = catalog.subjects.filter((subject) => subject.status === "active" && allowedIds.has(subject.id));
        const course = catalog.courses.find((item) => item.status === "active" && item.subjectIds.some((id) => allowedIds.has(id)));
        const board = catalog.examBoards.find((item) => item.status === "active" && item.id === course?.examBoardId);
        subscription.value = {
          examBoard: board?.name ?? "",
          level: course?.rawLevel || course?.level || "",
          subjects: selected.map((subject) => subject.name),
          topicsBySubject: selected.reduce<Record<string, string[]>>((result, subject) => {
            result[subject.name] = catalog.topics.filter((topic) => topic.status === "active" && topic.subjectId === subject.id).map((topic) => topic.name);
            return result;
          }, {}),
        };
        return;
      }
      if (teacher) {
        const onboarding = readOnboarding();
        const classes = listClasses();
        const selectedNames = new Set([...(onboarding.subjects ?? []), ...classes.map((item) => item.subject), onboarding.firstClass?.subject ?? ""].filter(Boolean));
        const selected = catalog.subjects.filter((subject) => subject.status === "active" && selectedNames.has(subject.name));
        const boardCandidate = onboarding.boards?.[0] ?? onboarding.firstClass?.board ?? classes[0]?.board ?? "";
        const levelCandidate = onboarding.quals?.[0] ?? onboarding.firstClass?.qualification ?? classes[0]?.qualification ?? "";
        const board = catalog.examBoards.find((item) => item.status === "active" && item.name === boardCandidate);
        const course = catalog.courses.find((item) => item.status === "active" && (item.name === levelCandidate || item.level === levelCandidate));
        const topicsBySubject = selected.reduce<Record<string, string[]>>((result, subject) => {
          result[subject.name] = catalog.topics.filter((topic) => topic.status === "active" && topic.subjectId === subject.id).map((topic) => topic.name);
          return result;
        }, {});
        subscription.value = {
          examBoard: board?.name ?? "",
          level: course?.level || course?.name || "",
          subjects: selected.map((subject) => subject.name),
          topicsBySubject,
        };
        return;
      }
      const selectedSubjectIds = new Set(account.subjects.map((subject) => subject.id));
      const topicsBySubject = account.subjects.reduce<Record<string, string[]>>((result, subject) => {
        result[subject.name] = catalog.topics.filter((topic) => topic.status === "active" && topic.subjectId === subject.id && selectedSubjectIds.has(topic.subjectId)).map((topic) => topic.name);
        return result;
      }, {});
      subscription.value = {
        examBoard: account.examBoard?.name ?? "",
        level: account.course?.level ?? "",
        subjects: account.subjects.map((subject) => subject.name),
        topicsBySubject,
      };
    }).catch(() => {
      // The consuming screen keeps its empty state and can surface its own API
      // error. Never replace a failed API call with invented academic data.
    });
  }
  return request;
}

export function useSubscription() {
  void hydrateAcademicSubscription();
  function update(patch: Partial<Subscription>) {
    subscription.value = { ...subscription.value, ...patch };
  }
  return [subscription, update] as const;
}
