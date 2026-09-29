import { API_FIELD_MAP as F } from "@/data/config/apiFieldMap";
import { asNumber, asStringArray, readField } from "@/data/core/readField";
import type { UiAccount, UiAccountBundle, UiCatalog, UiCourse, UiEntity, UiNotificationPreference, UiPlan, UiStudentPreferences, UiSubject, UiSubscription, UiTopic } from "@/data/models/platform";
import type { StudyLevel } from "@/views/student/data";

const object = (value: unknown): Record<string, unknown> => value && typeof value === "object" ? value as Record<string, unknown> : {};
const list = (value: unknown): unknown[] => Array.isArray(value) ? value : [];

function level(value: unknown): StudyLevel | "" {
  const normalized = String(value ?? "").toLowerCase().replaceAll("_", " ").trim();
  if (normalized === "o level") return "O Level";
  if (normalized === "gcse") return "GCSE";
  if (normalized === "igcse") return "IGCSE";
  if (normalized === "as level") return "AS Level";
  if (normalized === "a level") return "A Level";
  return "";
}

export function mapEntity(raw: unknown): UiEntity {
  const rawStatus = String(readField(raw, F.entity.status, ""));
  const explicitStatus = rawStatus === "enabled" ? "coming_soon" : rawStatus;
  const active = Boolean(readField(raw, ["is_active", "active"], true));
  return {
    id: asNumber(readField(raw, F.entity.id, 0)),
    name: String(readField(raw, F.entity.name, "")),
    code: String(readField(raw, F.entity.code, "")),
    status: explicitStatus || (active ? "active" : "inactive"),
  };
}

export function mapCourse(raw: unknown): UiCourse {
  const base = mapEntity(raw);
  const rawLevel = String(readField(raw, F.course.level, ""));
  const legacyBoardId = readField<number | null>(raw, F.course.examBoardId, null);
  const boardIds = list(readField(raw, ["exam_boards", "examBoards"], [])).map((board) => asNumber(readField(board, ["id"], 0))).filter(Boolean);
  return { ...base, level: level(rawLevel), rawLevel, examBoardId: legacyBoardId, boardIds: boardIds.length ? boardIds : legacyBoardId ? [legacyBoardId] : [], subjectIds: list(readField(raw, ["subjects"], [])).map((subject) => asNumber(readField(subject, ["id"], 0))).filter(Boolean) };
}

export function mapSubject(raw: unknown): UiSubject {
  return { ...mapEntity(raw), metadata: object(readField(raw, F.entity.metadata, {})) };
}

export function mapTopic(raw: unknown): UiTopic {
  return {
    ...mapEntity(raw),
    subjectId: asNumber(readField(raw, F.topic.subjectId, 0)),
    displayOrder: asNumber(readField(raw, F.topic.displayOrder, 0)),
    metadata: object(readField(raw, F.entity.metadata, {})),
  };
}

export function mapPlan(raw: unknown): UiPlan {
  const features = readField<unknown>(raw, ["features"], []);
  return {
    ...mapEntity(raw),
    scope: String(readField(raw, ["scope"], "")),
    courseId: readField<number | null>(raw, ["course_id", "courseId"], null),
    courseSubjects: (() => {
      const selections = list(readField(raw, ["course_subject_selections", "courseSubjectSelections"], []));
      const grouped = new Map<number, number[]>();
      selections.forEach((selection) => {
        const courseId = asNumber(readField(selection, ["course_id", "courseId"], 0));
        const subjectId = asNumber(readField(selection, ["subject_id", "subjectId"], 0));
        if (!courseId || !subjectId) return;
        grouped.set(courseId, [...(grouped.get(courseId) || []), subjectId]);
      });
      return [...grouped.entries()].map(([courseId, subjectIds]) => ({ courseId, subjectIds: [...new Set(subjectIds)] }));
    })(),
    subjectIds: list(readField(raw, ["subjects"], [])).map((subject) => asNumber(readField(subject, ["id"], 0))).filter(Boolean),
    priceMinor: asNumber(readField(raw, ["price_minor", "priceMinor"], 0)),
    currency: String(readField(raw, ["currency"], "USD")),
    billingInterval: String(readField(raw, ["billing_interval", "billingInterval"], "month")),
    trialDays: asNumber(readField(raw, ["trial_days", "trialDays"], 0)),
    features: Array.isArray(features)
      ? asStringArray(features)
      : Object.entries(object(features)).filter(([, enabled]) => Boolean(enabled)).map(([key]) => key.replaceAll("_", " ")),
    popular: ["all_subjects", "course"].includes(String(readField(raw, ["scope"], ""))),
  };
}

export function mapCatalog(raw: unknown): UiCatalog {
  return {
    examBoards: list(readField(raw, F.catalog.examBoards, [])).map(mapEntity),
    courses: list(readField(raw, F.catalog.courses, [])).map(mapCourse),
    subjects: list(readField(raw, F.catalog.subjects, [])).map(mapSubject),
    topics: list(readField(raw, F.catalog.topics, [])).map(mapTopic),
    plans: list(readField(raw, F.catalog.plans, [])).map(mapPlan),
    developmentPaymentBypass: Boolean(readField(raw, ["development_payment_bypass", "developmentPaymentBypass"], false)),
    mockPaymentsEnabled: Boolean(readField(raw, ["mock_payments_enabled", "mockPaymentsEnabled"], false)),
  };
}

function mapPreferences(raw: unknown): UiStudentPreferences {
  const p = object(raw);
  return {
    weakSubjects: asStringArray(readField(p, ["weak_subjects", "weakSubjects", "weak"], [])),
    notSureWeak: Boolean(readField(p, ["not_sure_weak", "notSureWeak"], false)),
    confidence: object(readField(p, ["confidence"], {})) as UiStudentPreferences["confidence"],
    targetGrades: object(readField(p, ["target_grades", "targetGrades", "targets"], {})) as Record<string, string>,
    examMonth: String(readField(p, ["exam_month", "examMonth"], "")),
    examYear: String(readField(p, ["exam_year", "examYear"], "")),
    topicIds: list(readField(p, ["topic_ids", "topicIds"], [])).map((value) => asNumber(value)).filter(Boolean),
    goal: String(readField(p, ["goal", "target_goal"], "")),
    primarySubject: String(readField(p, ["primary_subject", "primarySubject"], "")),
    targetGrade: String(readField(p, ["target_grade", "targetGrade"], "")),
    weeklyHours: asNumber(readField(p, ["weekly_hours", "weeklyHours"], 0)),
    examDate: String(readField(p, ["exam_date", "examDate"], "")),
    learningStyle: String(readField(p, ["learning_style", "learningStyle"], "")),
    difficulty: String(readField(p, ["difficulty"], "")),
    questionTypes: object(readField(p, ["question_types", "questionTypes"], {})) as Record<string, boolean>,
    feedback: object(readField(p, ["feedback"], {})) as Record<string, boolean>,
    sessionDefaults: {
      learnLen: asNumber(readField(p, ["session_defaults.learn_len", "sessionDefaults.learnLen"], 0)),
      examLen: asNumber(readField(p, ["session_defaults.exam_len", "sessionDefaults.examLen"], 0)),
      qCount: asNumber(readField(p, ["session_defaults.question_count", "sessionDefaults.qCount"], 0)),
    },
    coach: String(readField(p, ["coach", "feedback_tone"], "")),
    focus: String(readField(p, ["focus", "coaching_focus"], "")),
  };
}

export function mapAccount(raw: unknown): UiAccount {
  const profile = readField<unknown>(raw, F.account.profile, null);
  return {
    id: asNumber(readField(raw, F.account.id, 0)),
    name: String(readField(raw, F.account.name, "")),
    email: String(readField(raw, F.account.email, "")),
    profileId: profile ? asNumber(readField(profile, F.profile.id, 0)) : null,
    instituteId: profile ? readField<number | null>(profile, ["institute_id", "instituteId"], null) : null,
    schoolYear: String(readField(profile, F.profile.schoolYear, "")),
    schoolName: String(readField(profile, F.profile.schoolName, "")),
    course: readField(profile, F.profile.course, null) ? mapCourse(readField(profile, F.profile.course, null)) : null,
    examBoard: readField(profile, F.profile.examBoard, null) ? mapEntity(readField(profile, F.profile.examBoard, null)) : null,
    subjects: list(readField(profile, F.profile.subjects, [])).map(mapSubject),
    preferences: mapPreferences(readField(profile, F.profile.preferences, {})),
  };
}

export function mapNotification(raw: unknown): UiNotificationPreference {
  return {
    type: String(readField(raw, F.notification.type, "")),
    channel: readField(raw, F.notification.channel, "in_app"),
    enabled: Boolean(readField(raw, F.notification.enabled, false)),
    schedule: object(readField(raw, F.notification.schedule, {})),
  };
}

export function mapAccountBundle(raw: unknown): UiAccountBundle {
  return {
    account: mapAccount(readField(raw, ["data", "user"], {})),
    notifications: list(readField(raw, ["notification_preferences", "notificationPreferences"], [])).map(mapNotification),
  };
}

export function mapSubscription(raw: unknown): UiSubscription {
  const planRaw = readField<unknown>(raw, F.subscription.plan, null);
  return {
    id: asNumber(readField(raw, F.subscription.id, 0)),
    planId: asNumber(readField(raw, F.subscription.planId, 0)),
    status: String(readField(raw, F.subscription.status, "inactive")),
    periodEnd: String(readField(raw, F.subscription.periodEnd, "")),
    cancelAtPeriodEnd: Boolean(readField(raw, F.subscription.cancelAtPeriodEnd, false)),
    plan: planRaw ? mapPlan(planRaw) : null,
  };
}
