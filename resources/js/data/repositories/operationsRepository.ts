import { api, downloadApiFile } from "@/lib/api";
import {
  mapAccess,
  mapAudit,
  mapCatalogRecord,
  mapCommission,
  mapCommissionRule,
  mapContact,
  mapDevice,
  mapInstitute,
  mapInvoice,
  mapInstitutePrice,
  mapMember,
  mapPage,
  mapPayout,
  mapStudent,
  mapSubscription,
  mapUser,
  mapAccessGrant,
  mapTeachingClass,
  mapParentAccessLink,
  mapPayment,
  mapRefund,
} from "@/data/mappers/operationsMapper";
import { createBillingKey } from "@/data/billing.js";

const query = (values: Record<string, string | number | null | undefined>) => {
  const params = new URLSearchParams();
  Object.entries(values).forEach(([key, value]) => {
    if (value !== null && value !== undefined && String(value) !== "") params.set(key, String(value));
  });
  const encoded = params.toString();
  return encoded ? `?${encoded}` : "";
};

const page = async <T>(path: string, mapper: (raw: unknown) => T, params: Record<string, string | number | null | undefined> = {}) =>
  mapPage(await api(path + query(params)), mapper);

export const operationsRepository = {
  users: (params = {}) => page("/users", mapUser, params),
  students: (params = {}) => page("/students", mapStudent, params),
  institutes: (params = {}) => page("/institutes", mapInstitute, params),
  examBoards: (params = {}) => page("/exam-boards", mapCatalogRecord, params),
  courses: (params = {}) => page("/courses", mapCatalogRecord, params),
  subjects: (params = {}) => page("/subjects", mapCatalogRecord, params),
  topics: (params = {}) => page("/topics", mapCatalogRecord, params),
  plans: (params = {}) => page("/plans", mapCatalogRecord, params),
  discounts: (params = {}) => page("/discounts", mapCatalogRecord, params),
  roles: (params = {}) => page("/roles", mapAccess, params),
  permissions: (params = {}) => page("/permissions", mapAccess, params),
  subscriptions: (params = {}) => page("/subscriptions", mapSubscription, params),
  invoices: (params = {}) => page("/invoices", mapInvoice, params),
  payments: (params = {}) => page("/payments", mapPayment, params),
  refunds: (params = {}) => page("/refunds", mapRefund, params),
  commissionRules: (params = {}) => page("/commission-rules", mapCommissionRule, params),
  payouts: (params = {}) => page("/commission-payouts", mapPayout, params),
  commissions: (params = {}) => page("/institute-commissions", mapCommission, params),
  instituteSettlements: (instituteId: number, params = {}) => page(`/institutes/${instituteId}/settlements`, mapCommission, params),
  institutePlatformFees: (instituteId: number, params = {}) => page(`/institutes/${instituteId}/platform-fees`, mapCommission, params),
  instituteEarnings: (instituteId: number, params = {}) => page(`/institutes/${instituteId}/earnings`, mapCommission, params),
  institutePromoCodes: (instituteId: number) => api<{ data: Array<Record<string, unknown>> }>(`/institutes/${instituteId}/promo-codes`),
  createInstitutePromoCode: (instituteId: number, payload: Record<string, unknown>) => api(`/institutes/${instituteId}/promo-codes`, { method: "POST", body: JSON.stringify(payload) }),
  institutePromoReport: (instituteId: number, promoId: number) => api<{ data: Record<string, unknown> }>(`/institutes/${instituteId}/promo-codes/${promoId}/report`),
  adminPromotionsReferrals: (params: Record<string, string | number | null | undefined> = {}) => api<{ data: Record<string, unknown> }>(`/admin/promotions-referrals${query(params)}`),
  updateAdminInstitutePromoStatus: (promoId: number, isActive: boolean) => api(`/admin/institute-promo-codes/${promoId}/status`, { method: "PATCH", body: JSON.stringify({ is_active: isActive }) }),
  exportAdminPromotionsReferrals: (params: Record<string, string | number | null | undefined> = {}) => downloadApiFile(`/admin/promotions-referrals/export${query(params)}`, `preppy-promotions-referrals.csv`),
  devices: (params = {}) => page("/devices", mapDevice, params),
  contactMessages: (params = {}) => page("/contact-messages", mapContact, params),
  institutePrices: (params = {}) => page("/institute-plan-prices", mapInstitutePrice, params),
  audits: (params = {}) => page("/audit-logs", mapAudit, params),
  members: (instituteId: number, params = {}) => page(`/institutes/${instituteId}/members`, mapMember, params),
  pendingInvitations: () => api<{ data: import("@/data/models/operations").UiPendingInvitation[] }>("/admin/invitations"),
  activateInvitation: (type: string, id: number) => api(`/admin/invitations/${type}/${id}/activate`, { method: "POST" }),
  invitationLink: (type: string, id: number) => api<{ data: { activation_path: string; activation_url: string; invite_token: string } }>(`/admin/invitations/${type}/${id}/link`, { method: "POST" }),
  resendInvitation: (type: string, id: number) => api(`/admin/invitations/${type}/${id}/resend`, { method: "POST" }),
  extendInvitation: (type: string, id: number, days = 7) => api(`/admin/invitations/${type}/${id}/extend`, { method: "POST", body: JSON.stringify({ days }) }),
  revokeInvitation: (type: string, id: number) => api(`/admin/invitations/${type}/${id}`, { method: "DELETE" }),

  create: <T = unknown>(resource: string, payload: Record<string, unknown>) =>
    api<{ data: T }>(`/${resource}`, { method: "POST", body: JSON.stringify(payload) }),
  update: <T = unknown>(resource: string, id: number, payload: Record<string, unknown>) =>
    api<{ data: T }>(`/${resource}/${id}`, { method: "PUT", body: JSON.stringify(payload) }),
  remove: (resource: string, id: number) => api(`/${resource}/${id}`, { method: "DELETE" }),

  inviteMember: (instituteId: number, payload: { email: string; role_slug: string; expires_in_days: number }) =>
    api<{ data: unknown; invite_token: string }>(`/institutes/${instituteId}/members/invite`, { method: "POST", body: JSON.stringify(payload) }),
  removeMember: (instituteId: number, userId: number) => api(`/institutes/${instituteId}/members/${userId}`, { method: "DELETE" }),
  updateMember: (instituteId: number, userId: number, payload: Record<string, unknown>) => api(`/institutes/${instituteId}/members/${userId}`, { method: "PUT", body: JSON.stringify(payload) }),
  inviteEducator: (payload: { email: string; role_slug: "teacher" | "tutor"; institute_id?: number | null }) => api<{ data: unknown; invite_token: string; activation_path: string }>("/educator-invites", { method: "POST", body: JSON.stringify(payload) }),
  async educatorGrants(userId: number, instituteId?: number) { return (await api<{ data: unknown[] }>(`/educators/${userId}/access-grants${query({ institute_id: instituteId })}`)).data.map(mapAccessGrant); },
  grantEducatorAccess: (userId: number, payload: { institute_id?: number | null; scope_type: "class" | "course" | "student" | "subject"; scope_id: number; can_manage_parents?: boolean }) => api(`/educators/${userId}/access-grants`, { method: "POST", body: JSON.stringify(payload) }),
  revokeEducatorAccess: (userId: number, grantId: number) => api(`/educators/${userId}/access-grants/${grantId}`, { method: "DELETE" }),
  async teachingClasses(instituteId: number) { return (await api<{ data: unknown[] }>(`/institutes/${instituteId}/teaching-classes`)).data.map(mapTeachingClass); },
  createTeachingClass: (instituteId: number, payload: Record<string, unknown>) => api(`/institutes/${instituteId}/teaching-classes`, { method: "POST", body: JSON.stringify(payload) }),
  updateTeachingClass: (instituteId: number, classId: number, payload: Record<string, unknown>) => api(`/institutes/${instituteId}/teaching-classes/${classId}`, { method: "PUT", body: JSON.stringify(payload) }),
  removeTeachingClass: (instituteId: number, classId: number) => api(`/institutes/${instituteId}/teaching-classes/${classId}`, { method: "DELETE" }),
  createStudent: (instituteId: number, payload: Record<string, unknown>) =>
    api(`/institutes/${instituteId}/students`, { method: "POST", body: JSON.stringify(payload) }),
  findAvailableStudent: async (instituteId: number, email: string) => {
    const response = await api<{ data: unknown | null }>(`/institutes/${instituteId}/available-student${query({ email })}`);
    return response.data ? mapStudent(response.data) : null;
  },
  detachStudent: (studentId: number) => api(`/students/${studentId}/detach-institute`, { method: "POST" }),
  attachStudent: (instituteId: number, studentId: number) => api(`/institutes/${instituteId}/students/${studentId}/attach`, { method: "POST" }),
  inviteParent: (studentUserId: number, payload: Record<string, unknown>) => api<{ data: unknown; invite_token: string; activation_path: string }>(`/students/${studentUserId}/parents`, { method: "POST", body: JSON.stringify(payload) }),
  async parentLinks(studentUserId: number) { return (await api<{ data: unknown[] }>(`/students/${studentUserId}/parents`)).data.map(mapParentAccessLink); },
  revokeParentLink: (linkId: number) => api(`/parent-student-links/${linkId}`, { method: "DELETE" }),
  createSubscription: (studentProfileId: number, planId: number) =>
    api("/subscriptions", { method: "POST", body: JSON.stringify({ student_profile_id: studentProfileId, plan_id: planId }) }),
  changeSubscription: (id: number, action: "cancel" | "resume" | "pause" | "activate") =>
    api(`/subscriptions/${id}/${action}`, { method: "POST" }),
  markPayoutPaid: (id: number) => api(`/commission-payouts/${id}/mark-paid`, { method: "POST" }),
  createPayout: (instituteId: number, commissionIds: number[]) => api("/commission-payouts", { method: "POST", body: JSON.stringify({ institute_id: instituteId, commission_ids: commissionIds }) }),
  recordPayment: (invoiceId: number, payload: Record<string, unknown>) => api(`/invoices/${invoiceId}/payments`, { method: "POST", body: JSON.stringify(payload) }),
  checkoutInvoice: (invoiceId: number, provider: "jazzcash" | "google_pay" | "mock", idempotencyKey = createBillingKey("invoice")) => api<{ data: { payment: { id: number; status: string }; gateway: Record<string, unknown> } }>(`/invoices/${invoiceId}/checkout`, { method: "POST", headers: { "Idempotency-Key": idempotencyKey }, body: JSON.stringify({ provider }) }),
  captureGooglePay: (paymentId: number, paymentToken: string) => api(`/payments/${paymentId}/capture/google-pay`, { method: "POST", body: JSON.stringify({ payment_token: paymentToken }) }),
  captureMockPayment: (paymentId: number, paymentToken: "mock_success" | "mock_decline" = "mock_success") => api(`/payments/${paymentId}/capture/mock`, { method: "POST", body: JSON.stringify({ payment_token: paymentToken }) }),
  refundPayment: (paymentId: number, payload: { amount_minor: number; reason: string; revoke_entitlement: boolean }, idempotencyKey = createBillingKey("refund")) => api(`/payments/${paymentId}/refunds`, { method: "POST", headers: { "Idempotency-Key": idempotencyKey }, body: JSON.stringify(payload) }),
  downloadInvoice: (invoiceId: number, number: string) => downloadApiFile(`/invoices/${invoiceId}/pdf`, `${number}.pdf`),
  downloadReceipt: (paymentId: number, number: string) => downloadApiFile(`/payments/${paymentId}/receipt`, `${number || `receipt-${paymentId}`}.pdf`),
  downloadRefundReceipt: (refundId: number) => downloadApiFile(`/refunds/${refundId}/receipt`, `refund-${refundId}.pdf`),
  assignRoles: (userId: number, roles: Array<{ slug: string; context_type: "system" | "institute"; context_id: number | null }>) => api(`/users/${userId}/roles`, { method: "PUT", body: JSON.stringify({ roles }) }),
  audit: (id: number) => api<{ data: Record<string, unknown> }>(`/audit-logs/${id}`),
  updateContactStatus: (id: number, status: string) => api(`/contact-messages/${id}`, { method: "PATCH", body: JSON.stringify({ status }) }),
};
