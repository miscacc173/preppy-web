export interface UiPage<T> {
  items: T[];
  page: number;
  lastPage: number;
  total: number;
}

export interface UiUserRole {
  slug: string;
  instituteId: number | null;
}

export interface UiUserRecord {
  id: number;
  name: string;
  email: string;
  status: string;
  roles: UiUserRole[];
  lastLoginAt: string;
  timezone: string;
  locale: string;
}

export interface UiStudentRecord {
  id: number;
  userId: number;
  name: string;
  email: string;
  ownership: string;
  instituteId: number | null;
  instituteName: string;
  courseName: string;
  schoolYear: string;
  schoolName: string;
  status: string;
  subjectNames: string[];
  subjectIds: number[];
  courseId: number | null;
  examBoardId: number | null;
}

export interface UiInstituteRecord {
  id: number;
  name: string;
  code: string;
  slug: string;
  status: string;
  billingEmail: string;
  city: string;
  countryCode: string;
  timezone: string;
  settings: Record<string, unknown>;
}

export interface UiMemberRecord {
  id: number;
  userId: number;
  name: string;
  email: string;
  position: string;
  status: string;
  joinedAt: string;
  roleSlug: string;
}

export interface UiAccessGrant { id: number; educatorUserId: number; instituteId: number | null; scopeType: "class" | "course" | "student" | "subject"; scopeId: number; status: string; canManageParents: boolean }
export interface UiTeachingClass { id: number; name: string; status: string; courseId: number | null; courseName: string; subjectId: number | null; subjectName: string; teacherUserId: number | null; teacherName: string; studentUserIds: number[] }
export interface UiParentAccessLink { id: number; parentUserId: number; parentName: string; parentEmail: string; relationship: string; status: string; canViewPerformance: boolean; canViewSubscription: boolean; acceptedAt: string }

export interface UiPendingInvitation {
  id: number;
  type: "educator" | "institute" | "parent";
  email: string;
  role: string;
  context: string;
  expires_at: string;
  created_at: string;
  expired: boolean;
}

export interface UiCatalogRecord {
  id: number;
  name: string;
  code: string;
  status: string;
  detail: string;
  priceMinor: number;
  currency: string;
  scope: string;
  active: boolean;
  examBoardId: number | null;
  boardIds: number[];
  subjectId: number | null;
  subjectIds: number[];
  subjectNames: string[];
  courseId: number | null;
  courseName: string;
  qualificationFamily: string;
  billingInterval: string;
  billingIntervalCount: number;
  trialDays: number;
  discountType: string;
  percentage: number;
  amountMinor: number;
  displayOrder: number;
}

export interface UiSubscriptionRecord {
  id: number;
  subscriberId: number;
  planId: number;
  planName: string;
  status: string;
  periodEnd: string;
  cancelAtPeriodEnd: boolean;
  amountMinor: number;
  currency: string;
}

export interface UiInvoiceRecord {
  id: number;
  number: string;
  status: string;
  totalMinor: number;
  paidMinor: number;
  refundedMinor: number;
  currency: string;
  dueAt: string;
  retryCount: number;
  nextRetryAt: string;
  payments: UiPaymentRecord[];
}

export interface UiRefundRecord {
  id: number;
  paymentId: number;
  status: string;
  amountMinor: number;
  currency: string;
  reason: string;
  providerRefundId: string;
  processedAt: string;
}

export interface UiPaymentRecord {
  id: number;
  invoiceId: number;
  invoiceNumber: string;
  provider: string;
  providerPaymentId: string;
  status: string;
  amountMinor: number;
  refundedMinor: number;
  currency: string;
  receiptNumber: string;
  paidAt: string;
  attemptNumber: number;
  failureMessage: string;
  refunds: UiRefundRecord[];
}

export interface UiAccessRecord {
  id: number;
  name: string;
  slug: string;
  scope: string;
  group: string;
  permissions: string[];
  system: boolean;
}

export interface UiAuditRecord {
  id: number;
  action: string;
  actorId: number | null;
  target: string;
  createdAt: string;
}

export interface UiPayoutRecord {
  id: number;
  instituteId: number;
  reference: string;
  status: string;
  amountMinor: number;
  currency: string;
  paidAt: string;
}

export interface UiCommissionRecord {
  id: number;
  instituteId: number;
  studentUserId: number;
  status: string;
  amountMinor: number;
  currency: string;
  availableAt: string;
  direction: "platform_pays_institute" | "platform_collects_from_institute";
  settledAt: string;
  settlementType: "platform_fee" | "institute_earning";
  reversedAmountMinor: number;
  netAmountMinor: number;
}

export interface UiCommissionRuleRecord {
  id: number;
  instituteId: number;
  planId: number | null;
  type: string;
  fixedAmountMinor: number;
  percentage: number;
  currency: string;
  active: boolean;
  startsAt: string;
  endsAt: string;
  direction: "platform_pays_institute" | "platform_collects_from_institute";
}

export interface UiDeviceRecord {
  id: number;
  userId: number;
  platform: string;
  deviceId: string;
  timezone: string;
  active: boolean;
  lastSeenAt: string;
  pushToken: string;
}

export interface UiInstitutePriceRecord {
  id: number;
  instituteId: number;
  planId: number;
  priceMinor: number;
  currency: string;
  startsAt: string;
  endsAt: string;
  active: boolean;
}

export interface UiContactRecord {
  id: number;
  kind: string;
  name: string;
  email: string;
  subject: string;
  category: string;
  message: string;
  status: string;
  createdAt: string;
}

export interface UiOperationsOverview {
  users: number;
  students: number;
  institutes: number;
  subscriptions: number;
  recentUsers: UiUserRecord[];
  recentAudits: UiAuditRecord[];
}
