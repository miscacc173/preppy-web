/**
 * API -> UI key map.
 *
 * If your real API returns `full_name` instead of `name`, only update the
 * relevant path here. Arrays are aliases and are checked from left to right.
 */
export const API_FIELD_MAP = {
  account: {
    id: ["id", "user_id"],
    name: ["name", "full_name", "display_name"],
    email: ["email", "email_address"],
    profile: ["student_profile", "studentProfile", "profile"],
  },
  profile: {
    id: ["id", "student_profile_id"],
    schoolYear: ["school_year", "schoolYear", "academic_year"],
    schoolName: ["school_name", "schoolName"],
    course: ["course", "academic_course"],
    examBoard: ["exam_board", "examBoard", "board"],
    subjects: ["subjects", "selected_subjects"],
    preferences: ["preferences", "settings"],
  },
  catalog: {
    examBoards: ["exam_boards", "examBoards", "boards"],
    courses: ["courses", "qualifications"],
    subjects: ["subjects"],
    topics: ["topics"],
    plans: ["plans", "subscription_plans"],
  },
  entity: {
    id: ["id"],
    name: ["name", "title", "label"],
    code: ["code", "slug"],
    metadata: ["metadata", "stats", "analytics"],
    status: ["status", "availability_status", "availabilityStatus"],
  },
  course: {
    level: ["level", "qualification_level"],
    examBoardId: ["exam_board_id", "examBoardId"],
  },
  topic: {
    subjectId: ["subject_id", "subjectId"],
    status: ["status"],
    displayOrder: ["display_order", "displayOrder"],
  },
  subscription: {
    id: ["id", "subscription_id"],
    planId: ["plan_id", "planId"],
    status: ["status", "state"],
    plan: ["plan", "subscription_plan"],
    periodEnd: ["current_period_ends_at", "currentPeriodEndsAt", "renews_at"],
    cancelAtPeriodEnd: ["cancel_at_period_end", "cancelAtPeriodEnd"],
  },
  notification: {
    type: ["notification_type", "type"],
    channel: ["channel"],
    enabled: ["is_enabled", "enabled"],
    schedule: ["schedule"],
  },
  subjectStats: {
    readiness: ["metadata.readiness", "metadata.readiness_score", "readiness"],
    predictedGrade: ["metadata.predicted_grade", "metadata.predictedGrade", "predicted_grade"],
    confidence: ["metadata.confidence", "confidence"],
    paper: ["metadata.paper", "metadata.paper_name", "paper"],
    weak: ["metadata.weak_topic", "metadata.weakest_topic", "weak_topic"],
    strong: ["metadata.strong_topic", "metadata.strongest_topic", "strong_topic"],
    trend: ["metadata.trend", "trend"],
    examIn: ["metadata.exam_in", "metadata.days_to_exam", "exam_in"],
    examDate: ["metadata.exam_date", "exam_date"],
    strengths: ["metadata.strengths", "strengths"],
    weaknesses: ["metadata.weaknesses", "weaknesses"],
    missing: ["metadata.missing_marks", "metadata.missing", "missing"],
    suggested: ["metadata.suggested_actions", "metadata.suggested", "suggested"],
    recent: ["metadata.recent_activity", "metadata.recent", "recent"],
    recommendations: ["metadata.recommendations", "recommendations"],
    trendSeries: ["metadata.trend_series", "metadata.trendSeries", "trend_series"],
  },
} as const;
