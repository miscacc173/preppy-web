// Stable UI store for the Teacher Workspace creation workflows.
// The authenticated app-state synchronizer persists these records through the
// backend; this module keeps Vue components independent of that transport.

export type Onboarding = {
  displayName?: string;
  school?: string;
  role?: string;
  quals?: string[];
  boards?: string[];
  subjects?: string[];
  priorities?: string[];
  firstClass?: null | {
    name?: string;
    qualification?: string;
    board?: string;
    subject?: string;
    year?: string;
    size?: string;
  };
};

export function readOnboarding(): Onboarding {
  if (typeof window === "undefined") return {};
  try {
    const raw = localStorage.getItem("aistar:teacherOnboarding");
    return raw ? (JSON.parse(raw) as Onboarding) : {};
  } catch {
    return {};
  }
}

export type QuestionType =
  | "Multiple Choice"
  | "Short Answer"
  | "Structured"
  | "Extended Response"
  | "Data Analysis";

export type AssessmentQuestion = {
  id: string;
  text: string;
  type: QuestionType;
  topic: string;
  difficulty: "Easy" | "Medium" | "Hard";
  marks: number;
  expectedAnswer: string;
  markPoints: string[];
  guidance: string;
};

export type Assessment = {
  id: string;
  title: string;
  subject: string;
  qualification: string;
  board: string;
  className: string;
  topics: string[];
  questionSource: "AI Generated" | "Question Bank" | "Past Papers" | "Mixed";
  count: number;
  qtypes: QuestionType[];
  difficulty: "Easy" | "Medium" | "Hard" | "Mixed";
  totalMarks: number;
  duration: number;
  timed: boolean;
  dueDate: string;
  aiFeedback: boolean;
  status: "draft" | "assigned";
  questions: AssessmentQuestion[];
  assignedTo?: string;
  startDate?: string;
  instructions?: string;
  createdAt: string;
  updatedAt: string;
};

export type ActivitySection = {
  id: string;
  kind:
    | "objective"
    | "introduction"
    | "keyConcepts"
    | "explanation"
    | "workedExample"
    | "practice"
    | "hints"
    | "check"
    | "summary";
  title: string;
  body: string;
};

export type Activity = {
  id: string;
  title: string;
  subject: string;
  qualification: string;
  board: string;
  className: string;
  topic: string;
  objective: string;
  activityType:
    | "Guided Lesson"
    | "Topic Explanation"
    | "Revision Activity"
    | "Worked Example"
    | "Focused Practice"
    | "Exam Preparation";
  difficulty: "Easy" | "Medium" | "Hard";
  duration: number;
  components: {
    explanation: boolean;
    keyConcepts: boolean;
    workedExamples: boolean;
    practice: boolean;
    hints: boolean;
    feedback: boolean;
    summary: boolean;
  };
  sections: ActivitySection[];
  status: "draft" | "assigned";
  assignedTo?: string;
  startDate?: string;
  dueDate?: string;
  instructions?: string;
  createdAt: string;
  updatedAt: string;
};

export type Practice = {
  id: string;
  title: string;
  className: string;
  subject: string;
  qualification: string;
  board: string;
  topics: string[];
  practiceType:
    | "Topic Practice"
    | "Weak-Topic Practice"
    | "Revision Practice"
    | "Exam-Style Practice";
  difficulty: "Easy" | "Medium" | "Hard" | "Mixed";
  count: number;
  duration: number;
  dueDate: string;
  aiFeedback: boolean;
  status: "assigned";
  createdAt: string;
};

export type TeacherClass = {
  id: string;
  name: string;
  subject: string;
  qualification: string;
  board: string;
  year?: string;
  size?: string;
};

// ---------- localStorage helpers ----------
const K_ASSESS = "aistar:teacherAssessments";
const K_ACT = "aistar:teacherActivities";
const K_PRAC = "aistar:teacherPractice";
const K_CLASSES = "aistar:teacherClasses";
const K_DRAFT_A = "aistar:teacherAssessmentDraft";
const K_DRAFT_L = "aistar:teacherActivityDraft";
const K_DRAFT_P = "aistar:teacherPracticeDraft";

function read<T>(k: string, fallback: T): T {
  if (typeof window === "undefined") return fallback;
  try {
    const raw = localStorage.getItem(k);
    return raw ? (JSON.parse(raw) as T) : fallback;
  } catch {
    return fallback;
  }
}
function write<T>(k: string, v: T) {
  try {
    localStorage.setItem(k, JSON.stringify(v));
  } catch {
    /* ignore */
  }
}

export const listAssessments = () => read<Assessment[]>(K_ASSESS, []);
export const getAssessment = (id: string) =>
  listAssessments().find((a) => a.id === id) || null;
export function saveAssessment(a: Assessment) {
  const all = listAssessments();
  const i = all.findIndex((x) => x.id === a.id);
  a.updatedAt = new Date().toISOString();
  if (i >= 0) all[i] = a;
  else all.unshift(a);
  write(K_ASSESS, all);
  return a;
}
export function deleteAssessment(id: string) {
  write(K_ASSESS, listAssessments().filter((a) => a.id !== id));
}

export const listActivities = () => read<Activity[]>(K_ACT, []);
export const getActivity = (id: string) =>
  listActivities().find((a) => a.id === id) || null;
export function saveActivity(a: Activity) {
  const all = listActivities();
  const i = all.findIndex((x) => x.id === a.id);
  a.updatedAt = new Date().toISOString();
  if (i >= 0) all[i] = a;
  else all.unshift(a);
  write(K_ACT, all);
  return a;
}
export function deleteActivity(id: string) {
  write(K_ACT, listActivities().filter((a) => a.id !== id));
}

export const listPractice = () => read<Practice[]>(K_PRAC, []);
export function savePractice(p: Practice) {
  const all = listPractice();
  all.unshift(p);
  write(K_PRAC, all);
  return p;
}
export function deletePractice(id: string) {
  write(K_PRAC, listPractice().filter((p) => p.id !== id));
}

export function listClasses(): TeacherClass[] {
  const arr = read<TeacherClass[]>(K_CLASSES, []);
  if (arr.length) return arr;
  const ob = readOnboarding();
  if (ob.firstClass?.name) {
    return [{
      id: "onboarding-class",
      name: ob.firstClass.name,
      subject: ob.firstClass.subject || "",
      qualification: ob.firstClass.qualification || "",
      board: ob.firstClass.board || "",
      year: ob.firstClass.year,
      size: ob.firstClass.size,
    }];
  }
  return [];
}
export function saveClass(c: TeacherClass) {
  const stored = read<TeacherClass[]>(K_CLASSES, []);
  // If we're still on the onboarding-derived list, materialise it first.
  const base = stored.length ? stored : listClasses().filter((x) => x.id !== "onboarding-class").concat(
    listClasses().filter((x) => x.id === "onboarding-class")
  );
  const i = base.findIndex((x) => x.id === c.id);
  if (i >= 0) base[i] = c;
  else base.unshift(c);
  write(K_CLASSES, base);
  return c;
}
export function deleteClass(id: string) {
  const stored = read<TeacherClass[]>(K_CLASSES, []);
  const base = stored.length ? stored : listClasses();
  write(K_CLASSES, base.filter((c) => c.id !== id));
}

export const readAssessmentDraft = () =>
  read<Partial<Assessment> | null>(K_DRAFT_A, null);
export const writeAssessmentDraft = (v: Partial<Assessment> | null) =>
  v ? write(K_DRAFT_A, v) : localStorage.removeItem(K_DRAFT_A);

export const readActivityDraft = () =>
  read<Partial<Activity> | null>(K_DRAFT_L, null);
export const writeActivityDraft = (v: Partial<Activity> | null) =>
  v ? write(K_DRAFT_L, v) : localStorage.removeItem(K_DRAFT_L);

export const readPracticeDraft = () =>
  read<Partial<Practice> | null>(K_DRAFT_P, null);
export const writePracticeDraft = (v: Partial<Practice> | null) =>
  v ? write(K_DRAFT_P, v) : localStorage.removeItem(K_DRAFT_P);

// ---------- ID + Generators ----------
export const uid = () =>
  (typeof crypto !== "undefined" && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36));

const STEMS: Record<string, string[]> = {
  Mathematics: [
    "Solve for x in the equation {topic} shown below.",
    "A student is investigating {topic}. Explain the key relationship they should identify.",
    "Calculate the value using the {topic} rule. Show each step of your working.",
    "Compare two approaches to {topic} and justify which is more efficient.",
  ],
  Physics: [
    "Describe how {topic} applies in the scenario shown, including relevant units.",
    "A {topic} experiment produces the results below. Interpret the data.",
    "Explain the underlying principle of {topic} using a labelled diagram where useful.",
  ],
  Chemistry: [
    "Explain what happens during {topic} at a particle level.",
    "Predict the outcome of the reaction related to {topic} and justify your answer.",
    "Describe how {topic} is investigated in a school laboratory, referencing safety.",
  ],
  Biology: [
    "Describe the process of {topic} and its role in the organism.",
    "Explain how {topic} is adapted to its function.",
    "Compare the ways {topic} occurs in two different systems.",
  ],
  English: [
    "Analyse how the writer presents ideas about {topic} in the extract.",
    "Compare how {topic} is explored across the two texts.",
    "Write a response evaluating the effectiveness of {topic}.",
  ],
  History: [
    "Explain the significance of {topic} in this period.",
    "Assess how far {topic} was the main cause of the change shown.",
    "Describe two features of {topic}.",
  ],
};
const DEFAULT_STEMS = [
  "Explain the concept of {topic} clearly in your own words.",
  "Apply your knowledge of {topic} to the scenario provided.",
  "Evaluate one strength and one limitation of {topic}.",
];

function pickStems(subject: string) {
  return STEMS[subject] || DEFAULT_STEMS;
}

export function generateAssessmentQuestions(
  a: Pick<
    Assessment,
    "subject" | "topics" | "count" | "qtypes" | "difficulty" | "totalMarks"
  >,
): AssessmentQuestion[] {
  const stems = pickStems(a.subject);
  const topics = a.topics.length ? a.topics : ["Core content"];
  const types: QuestionType[] = a.qtypes.length ? a.qtypes : ["Short Answer"];
  const diffs: AssessmentQuestion["difficulty"][] =
    a.difficulty === "Mixed"
      ? ["Easy", "Medium", "Hard"]
      : [a.difficulty];
  const perQ = Math.max(1, Math.round(a.totalMarks / Math.max(1, a.count)));
  const out: AssessmentQuestion[] = [];
  for (let i = 0; i < a.count; i++) {
    const topic = topics[i % topics.length];
    const type = types[i % types.length];
    const stem = stems[i % stems.length].replace("{topic}", topic);
    const difficulty = diffs[i % diffs.length];
    out.push({
      id: uid(),
      text: stem,
      type,
      topic,
      difficulty,
      marks: perQ,
      expectedAnswer: `A model answer for "${topic}" demonstrating clear reasoning and correct use of subject terminology.`,
      markPoints: [
        `Correctly identifies the key idea in ${topic} (1 mark)`,
        `Applies it to the question context (1 mark)`,
        `Uses accurate subject terminology (1 mark)`,
      ],
      guidance: `Award marks for clarity of explanation and correct use of ${a.subject.toLowerCase()} terminology. Allow equivalent wording.`,
    });
  }
  return out;
}

export function regenerateQuestion(
  q: AssessmentQuestion,
  subject: string,
): AssessmentQuestion {
  const stems = pickStems(subject);
  const stem = stems[Math.floor(Math.random() * stems.length)].replace(
    "{topic}",
    q.topic,
  );
  return { ...q, id: uid(), text: stem };
}

export function generateActivitySections(a: Activity): ActivitySection[] {
  const s: ActivitySection[] = [];
  s.push({
    id: uid(),
    kind: "objective",
    title: "Learning Objective",
    body:
      a.objective ||
      `By the end of this activity, students will understand ${a.topic} and be able to apply it in exam-style questions.`,
  });
  s.push({
    id: uid(),
    kind: "introduction",
    title: "Topic Introduction",
    body: `${a.topic} is a core area of ${a.subject} in the ${a.qualification} ${a.board} specification. This activity introduces the essentials and builds towards independent practice.`,
  });
  if (a.components.keyConcepts) {
    s.push({
      id: uid(),
      kind: "keyConcepts",
      title: "Key Concepts",
      body: `• Core idea of ${a.topic}\n• Common terminology students should use\n• The most frequent misconceptions to watch for`,
    });
  }
  if (a.components.explanation) {
    s.push({
      id: uid(),
      kind: "explanation",
      title: "Guided Explanation",
      body: `A step-by-step walkthrough of ${a.topic}, written in student-friendly language and aligned with ${a.board} expectations.`,
    });
  }
  if (a.components.workedExamples) {
    s.push({
      id: uid(),
      kind: "workedExample",
      title: "Worked Example",
      body: `Example: A typical ${a.qualification} question on ${a.topic}, solved with reasoning at each step.`,
    });
  }
  if (a.components.practice) {
    s.push({
      id: uid(),
      kind: "practice",
      title: "Student Practice",
      body: `Three short practice questions on ${a.topic} for students to attempt independently.`,
    });
  }
  if (a.components.hints) {
    s.push({
      id: uid(),
      kind: "hints",
      title: "Hints & Support",
      body: `Optional hints students can reveal if they get stuck, without giving away the full answer.`,
    });
  }
  s.push({
    id: uid(),
    kind: "check",
    title: "Quick Knowledge Check",
    body: `A short set of check-your-understanding questions on ${a.topic}.`,
  });
  if (a.components.summary) {
    s.push({
      id: uid(),
      kind: "summary",
      title: "Activity Summary",
      body: `Recap the key points about ${a.topic} and highlight what students should revise next.`,
    });
  }
  return s;
}
