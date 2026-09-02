import type { UiCatalog, UiCourse, UiEntity, UiSubject } from "@/data/models/platform";

export function activeCourses(catalog: UiCatalog | null): UiCourse[] {
  return (catalog?.courses ?? []).filter((course) => ["active", "coming_soon"].includes(course.status));
}

export function boardsForCourse(catalog: UiCatalog | null, courseId: number | null): UiEntity[] {
  const course = catalog?.courses.find((item) => item.id === courseId);
  if (!course) return [];
  const allowed = new Set(course.boardIds);
  return (catalog?.examBoards ?? []).filter((board) => allowed.has(board.id));
}

export function subjectsForCourse(catalog: UiCatalog | null, courseId: number | null): UiSubject[] {
  const course = catalog?.courses.find((item) => item.id === courseId);
  if (!course) return [];
  const allowed = new Set(course.subjectIds);
  return (catalog?.subjects ?? []).filter((subject) => allowed.has(subject.id));
}

export function sanitizeAcademicSelection(catalog: UiCatalog | null, courseId: number | null, boardId: number | null, subjectIds: number[]) {
  const boards = boardsForCourse(catalog, courseId);
  const subjects = subjectsForCourse(catalog, courseId);
  const allowedBoardIds = new Set(boards.filter((item) => item.status === "active").map((item) => item.id));
  const allowedSubjectIds = new Set(subjects.filter((item) => item.status === "active").map((item) => item.id));
  return {
    courseId,
    boardId: boardId && allowedBoardIds.has(boardId) ? boardId : null,
    subjectIds: subjectIds.filter((id) => allowedSubjectIds.has(id)),
  };
}

export function academicSelectionComplete(catalog: UiCatalog | null, courseId: number | null, boardId: number | null, subjectIds: number[]) {
  const course = catalog?.courses.find((item) => item.id === courseId && item.status === "active");
  if (!course || !boardId || !subjectIds.length) return false;
  const boardIds = new Set(boardsForCourse(catalog, courseId).filter((item) => item.status === "active").map((item) => item.id));
  const allowedSubjectIds = new Set(subjectsForCourse(catalog, courseId).filter((item) => item.status === "active").map((item) => item.id));
  return boardIds.has(boardId) && subjectIds.every((id) => allowedSubjectIds.has(id));
}
