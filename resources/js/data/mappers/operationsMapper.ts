import { asNumber, readField } from "@/data/core/readField";
import type {
  UiAccessRecord,
  UiAuditRecord,
  UiCatalogRecord,
  UiCommissionRecord,
  UiCommissionRuleRecord,
  UiContactRecord,
  UiDeviceRecord,
  UiInstituteRecord,
  UiInvoiceRecord,
  UiInstitutePriceRecord,
  UiMemberRecord,
  UiPage,
  UiPayoutRecord,
  UiStudentRecord,
  UiSubscriptionRecord,
  UiUserRecord,
  UiAccessGrant,
  UiTeachingClass,
  UiParentAccessLink,
  UiPaymentRecord,
  UiRefundRecord,
} from "@/data/models/operations";

const list = (value: unknown): unknown[] => Array.isArray(value) ? value : [];
const text = (source: unknown, paths: string | string[], fallback = "") => String(readField(source, paths, fallback) ?? fallback);
const number = (source: unknown, paths: string | string[], fallback = 0) => asNumber(readField(source, paths, fallback));

export function mapPage<T>(raw: unknown, mapper: (item: unknown) => T): UiPage<T> {
  return {
    items: list(readField(raw, ["data", "items"], [])).map(mapper),
    page: number(raw, ["current_page", "currentPage"], 1),
    lastPage: number(raw, ["last_page", "lastPage"], 1),
    total: number(raw, "total", 0),
  };
}

export function mapUser(raw: unknown): UiUserRecord {
  return {
    id: number(raw, "id"),
    name: text(raw, ["name", "full_name"]),
    email: text(raw, "email"),
    status: text(raw, "status", "active"),
    roles: list(readField(raw, "roles", [])).map((role) => ({
      slug: text(role, "slug"),
      instituteId: readField<number | null>(role, ["pivot.context_id", "institute_id"], null),
    })),
    lastLoginAt: text(raw, ["last_login_at", "lastLoginAt"]),
    timezone: text(raw, "timezone", "UTC"),
    locale: text(raw, "locale", "en"),
  };
}

export function mapStudent(raw: unknown): UiStudentRecord {
  const user = readField(raw, "user", {});
  return {
    id: number(raw, "id"),
    userId: number(raw, ["user_id", "userId"]),
    name: text(user, ["name", "full_name"], text(raw, "name")),
    email: text(user, "email", text(raw, "email")),
    ownership: text(raw, ["ownership_type", "ownershipType"]),
    instituteId: readField<number | null>(raw, ["institute_id", "instituteId"], null),
    instituteName: text(raw, ["institute.name", "institute_name"]),
    courseName: text(raw, ["course.name", "course_name"]),
    schoolYear: text(raw, ["school_year", "schoolYear"]),
    schoolName: text(raw, ["school_name", "schoolName"]),
    status: text(raw, "status", "active"),
    subjectNames: list(readField(raw, "subjects", [])).map((subject) => text(subject, "name")).filter(Boolean),
    subjectIds: list(readField(raw, "subjects", [])).map((subject) => number(subject, "id")).filter(Boolean),
    courseId: readField<number | null>(raw, ["course_id", "courseId"], null),
    examBoardId: readField<number | null>(raw, ["exam_board_id", "examBoardId"], null),
  };
}

export function mapInstitute(raw: unknown): UiInstituteRecord {
  return {
    id: number(raw, "id"),
    name: text(raw, "name"),
    code: text(raw, "code"),
    slug: text(raw, "slug"),
    status: text(raw, "status", "active"),
    billingEmail: text(raw, ["billing_email", "billingEmail"]),
    city: text(raw, "city"),
    countryCode: text(raw, ["country_code", "countryCode"]),
    timezone: text(raw, "timezone", "UTC"),
    settings: readField<Record<string, unknown>>(raw, "settings", {}),
  };
}

export function mapMember(raw: unknown): UiMemberRecord {
  const user = readField(raw, "user", {});
  const roles = list(readField(user, "roles", []));
  return {
    id: number(raw, "id"),
    userId: number(raw, ["user_id", "userId"]),
    name: text(user, "name"),
    email: text(user, "email"),
    position: text(raw, "position"),
    status: text(raw, "status", "active"),
    joinedAt: text(raw, ["joined_at", "joinedAt"]),
    roleSlug: text(roles.find((role) => ["teacher", "tutor", "institute_admin", "institute_staff"].includes(text(role, "slug"))) || roles[0], "slug"),
  };
}

export function mapAccessGrant(raw: unknown): UiAccessGrant {
  return { id: number(raw, "id"), educatorUserId: number(raw, ["educator_user_id"]), instituteId: readField<number | null>(raw, ["institute_id"], null), scopeType: text(raw, ["scope_type"], "student") as UiAccessGrant["scopeType"], scopeId: number(raw, ["scope_id"]), status: text(raw, "status"), canManageParents: Boolean(readField(raw, ["can_manage_parents"], false)) };
}

export function mapTeachingClass(raw: unknown): UiTeachingClass {
  return { id: number(raw, "id"), name: text(raw, "name"), status: text(raw, "status"), courseId: readField<number | null>(raw, ["course_id"], null), courseName: text(raw, ["course.name"]), subjectId: readField<number | null>(raw, ["subject_id"], null), subjectName: text(raw, ["subject.name"]), teacherUserId: readField<number | null>(raw, ["teacher_user_id"], null), teacherName: text(raw, ["teacher.name"]), studentUserIds: list(readField(raw, "students", [])).map((student) => number(student, "id")) };
}

export function mapParentAccessLink(raw: unknown): UiParentAccessLink {
  return {
    id: number(raw, "id"),
    parentUserId: number(raw, ["parent_user_id"]),
    parentName: text(raw, ["parent.name"]),
    parentEmail: text(raw, ["parent.email"]),
    relationship: text(raw, "relationship", "Parent"),
    status: text(raw, "status", "pending"),
    canViewPerformance: Boolean(readField(raw, ["can_view_performance"], false)),
    canViewSubscription: Boolean(readField(raw, ["can_view_subscription"], false)),
    acceptedAt: text(raw, ["accepted_at"]),
  };
}

export function mapCatalogRecord(raw: unknown): UiCatalogRecord {
  const interval = text(raw, ["billing_interval", "billingInterval"]);
  const rawStatus = text(raw, ["status", "scope"], readField(raw, "is_active", true) ? "active" : "inactive");
  return {
    id: number(raw, "id"),
    name: text(raw, "name"),
    code: text(raw, "code"),
    status: rawStatus === "enabled" ? "coming_soon" : rawStatus,
    detail: text(raw, ["level", "subject.name", "qualification_family", "discount_type", "scope"], interval),
    priceMinor: number(raw, ["price_minor", "amount_minor", "priceMinor"]),
    currency: text(raw, "currency", "GBP"),
    scope: text(raw, "scope"),
    active: Boolean(readField(raw, ["is_active", "active"], true)),
    examBoardId: readField<number | null>(raw, ["exam_board_id", "examBoardId"], null),
    boardIds: list(readField(raw, ["exam_boards", "examBoards"], [])).map((board) => number(board, "id")).filter(Boolean),
    subjectId: readField<number | null>(raw, ["subject_id", "subjectId"], null),
    subjectIds: list(readField(raw, ["subjects"], [])).map((subject) => number(subject, "id")).filter(Boolean),
    subjectNames: list(readField(raw, ["subjects"], [])).map((subject) => text(subject, "name")).filter(Boolean),
    courseId: readField<number | null>(raw, ["course_id", "courseId"], null),
    courseName: text(raw, ["course.name", "courseName"]),
    courseSubjects: (() => {
      const grouped = new Map<number, number[]>();
      list(readField(raw, ["course_subject_selections", "courseSubjectSelections"], [])).forEach((selection) => {
        const courseId = number(selection, ["course_id", "courseId"]);
        const subjectId = number(selection, ["subject_id", "subjectId"]);
        if (courseId && subjectId) grouped.set(courseId, [...(grouped.get(courseId) || []), subjectId]);
      });
      return [...grouped.entries()].map(([courseId, subjectIds]) => ({ courseId, subjectIds: [...new Set(subjectIds)] }));
    })(),
    qualificationFamily: text(raw, ["qualification_family", "qualificationFamily"]),
    billingInterval: text(raw, ["billing_interval", "billingInterval"], "month"),
    billingIntervalCount: number(raw, ["billing_interval_count", "billingIntervalCount"], 1),
    trialDays: number(raw, ["trial_days", "trialDays"], 0),
    discountType: text(raw, ["discount_type", "discountType"]),
    percentage: number(raw, "percentage"),
    amountMinor: number(raw, ["amount_minor", "amountMinor"]),
    displayOrder: number(raw, ["display_order", "displayOrder"]),
  };
}

export function mapSubscription(raw: unknown): UiSubscriptionRecord {
  return {
    id: number(raw, "id"),
    subscriberId: number(raw, ["subscriber_id", "subscriberId"]),
    planId: number(raw, ["plan_id", "planId"]),
    planName: text(raw, ["plan.name", "plan_name"], `Plan #${number(raw, ["plan_id", "planId"])}`),
    status: text(raw, "status"),
    periodEnd: text(raw, ["current_period_ends_at", "currentPeriodEnd"]),
    cancelAtPeriodEnd: Boolean(readField(raw, ["cancel_at_period_end", "cancelAtPeriodEnd"], false)),
    amountMinor: number(raw, ["items.0.unit_price_minor", "amount_minor"]),
    currency: text(raw, ["items.0.currency", "currency"], "GBP"),
  };
}

export function mapInvoice(raw: unknown): UiInvoiceRecord {
  return {
    id: number(raw, "id"),
    number: text(raw, ["invoice_number", "invoiceNumber"]),
    status: text(raw, "status"),
    totalMinor: number(raw, ["total_minor", "totalMinor"]),
    paidMinor: number(raw, ["paid_minor", "paidMinor"]),
    refundedMinor: number(raw, ["refunded_minor", "refundedMinor"]),
    currency: text(raw, "currency", "GBP"),
    dueAt: text(raw, ["due_at", "dueAt"]),
    retryCount: number(raw, ["retry_count", "retryCount"]),
    nextRetryAt: text(raw, ["next_retry_at", "nextRetryAt"]),
    payments: list(readField(raw, "payments", [])).map(mapPayment),
  };
}

export function mapRefund(raw: unknown): UiRefundRecord {
  return { id: number(raw, "id"), paymentId: number(raw, ["payment_id", "paymentId"]), status: text(raw, "status"), amountMinor: number(raw, ["amount_minor", "amountMinor"]), currency: text(raw, "currency", "PKR"), reason: text(raw, "reason"), providerRefundId: text(raw, ["provider_refund_id", "providerRefundId"]), processedAt: text(raw, ["processed_at", "processedAt"]) };
}

export function mapPayment(raw: unknown): UiPaymentRecord {
  return { id: number(raw, "id"), invoiceId: number(raw, ["invoice_id", "invoiceId"]), invoiceNumber: text(raw, ["invoice.invoice_number", "invoiceNumber"]), provider: text(raw, "provider"), providerPaymentId: text(raw, ["provider_payment_id", "providerPaymentId"]), status: text(raw, "status"), amountMinor: number(raw, ["amount_minor", "amountMinor"]), refundedMinor: number(raw, ["refunded_minor", "refundedMinor"]), currency: text(raw, "currency", "PKR"), receiptNumber: text(raw, ["receipt_number", "receiptNumber"]), paidAt: text(raw, ["paid_at", "paidAt"]), attemptNumber: number(raw, ["attempt_number", "attemptNumber"], 1), failureMessage: text(raw, ["failure_message", "failureMessage"]), refunds: list(readField(raw, "refunds", [])).map(mapRefund) };
}

export function mapAccess(raw: unknown): UiAccessRecord {
  return {
    id: number(raw, "id"),
    name: text(raw, "name"),
    slug: text(raw, "slug"),
    scope: text(raw, "scope"),
    group: text(raw, "group"),
    permissions: list(readField(raw, "permissions", [])).map((permission) => text(permission, "slug")).filter(Boolean),
    system: Boolean(readField(raw, ["is_system", "system"], false)),
  };
}

export function mapAudit(raw: unknown): UiAuditRecord {
  return {
    id: number(raw, "id"),
    action: text(raw, "action"),
    actorId: readField<number | null>(raw, ["actor_user_id", "actorUserId"], null),
    target: [text(raw, ["auditable_type", "auditableType"]), text(raw, ["auditable_id", "auditableId"])].filter(Boolean).join(" #"),
    createdAt: text(raw, ["created_at", "createdAt"]),
  };
}

export function mapPayout(raw: unknown): UiPayoutRecord {
  return {
    id: number(raw, "id"),
    instituteId: number(raw, ["institute_id", "instituteId"]),
    reference: text(raw, "reference"),
    status: text(raw, "status"),
    amountMinor: number(raw, ["amount_minor", "amountMinor"]),
    currency: text(raw, "currency", "GBP"),
    paidAt: text(raw, ["paid_at", "paidAt"]),
  };
}

export function mapCommission(raw: unknown): UiCommissionRecord {
  return {
    id: number(raw, "id"),
    instituteId: number(raw, ["institute_id", "instituteId"]),
    studentUserId: number(raw, ["student_user_id", "studentUserId"]),
    status: text(raw, "status"),
    amountMinor: number(raw, ["commission_amount_minor", "commissionAmountMinor"]),
    currency: text(raw, "currency", "GBP"),
    availableAt: text(raw, ["available_at", "availableAt"]),
    direction: text(raw, "direction", "platform_pays_institute") as UiCommissionRecord["direction"],
    settledAt: text(raw, ["settled_at", "settledAt"]),
    settlementType: text(raw, ["settlement_type", "settlementType"], text(raw, "direction") === "platform_collects_from_institute" ? "platform_fee" : "institute_earning") as UiCommissionRecord["settlementType"],
    reversedAmountMinor: number(raw, ["reversed_amount_minor", "reversedAmountMinor"]),
    netAmountMinor: number(raw, ["net_amount_minor", "netAmountMinor"], number(raw, ["commission_amount_minor", "commissionAmountMinor"])),
  };
}

export function mapCommissionRule(raw: unknown): UiCommissionRuleRecord {
  return {
    id: number(raw, "id"),
    instituteId: number(raw, ["institute_id", "instituteId"]),
    planId: readField<number | null>(raw, ["plan_id", "planId"], null),
    type: text(raw, ["commission_type", "type"], "percentage"),
    fixedAmountMinor: number(raw, ["fixed_amount_minor", "fixedAmountMinor"]),
    percentage: number(raw, "percentage"),
    currency: text(raw, "currency", "GBP"),
    active: Boolean(readField(raw, ["is_active", "active"], true)),
    startsAt: text(raw, ["starts_at", "startsAt"]),
    endsAt: text(raw, ["ends_at", "endsAt"]),
    direction: text(raw, "direction", "platform_pays_institute") as UiCommissionRuleRecord["direction"],
  };
}

export function mapDevice(raw: unknown): UiDeviceRecord {
  return {
    id: number(raw, "id"),
    userId: number(raw, ["user_id", "userId"]),
    platform: text(raw, "platform"),
    deviceId: text(raw, ["device_id", "deviceId"]),
    timezone: text(raw, "timezone", "UTC"),
    active: Boolean(readField(raw, ["is_active", "active"], false)),
    lastSeenAt: text(raw, ["last_seen_at", "lastSeenAt"]),
    pushToken: text(raw, ["push_token", "pushToken"]),
  };
}

export function mapInstitutePrice(raw: unknown): UiInstitutePriceRecord {
  return {
    id: number(raw, "id"),
    instituteId: number(raw, ["institute_id", "instituteId"]),
    planId: number(raw, ["plan_id", "planId"]),
    priceMinor: number(raw, ["price_minor", "priceMinor"]),
    currency: text(raw, "currency", "GBP"),
    startsAt: text(raw, ["starts_at", "startsAt"]),
    endsAt: text(raw, ["ends_at", "endsAt"]),
    active: Boolean(readField(raw, ["is_active", "active"], false)),
  };
}

export function mapContact(raw: unknown): UiContactRecord {
  return {
    id: number(raw, "id"),
    kind: text(raw, "kind", "contact"),
    name: text(raw, "name"),
    email: text(raw, "email"),
    subject: text(raw, "subject"),
    category: text(raw, "category"),
    message: text(raw, "message"),
    status: text(raw, "status", "open"),
    createdAt: text(raw, ["created_at", "createdAt"]),
  };
}
