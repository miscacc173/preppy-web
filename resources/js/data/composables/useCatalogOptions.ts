import { readonly, ref } from "vue";
import { platformRepository } from "@/data/repositories/platformRepository";

const qualifications = ref<string[]>([]);
const boards = ref<string[]>([]);
const subjects = ref<string[]>([]);
const loading = ref(false);
let request: Promise<void> | null = null;

function load() {
  if (!request) {
    loading.value = true;
    request = platformRepository.catalog().then((catalog) => {
      qualifications.value = Array.from(new Set(catalog.courses.filter((course) => course.status === "active").map((course) => course.level).filter(Boolean)));
      boards.value = catalog.examBoards.filter((board) => board.status === "active").map((board) => board.name);
      subjects.value = catalog.subjects.filter((subject) => subject.status === "active").map((subject) => subject.name);
    }).finally(() => { loading.value = false; });
  }
  return request;
}

/** Shared Laravel catalog options for teacher/student form controls. */
export function useCatalogOptions() {
  void load().catch(() => undefined);
  return { qualifications: readonly(qualifications), boards: readonly(boards), subjects: readonly(subjects), loading: readonly(loading), reload: load };
}
