export function createBillingKey(prefix = "billing") {
  const random = globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
  return `${prefix}-${random}`.slice(0, 100);
}

export function outstandingAmount(invoice) {
  return Math.max(0, Number(invoice.totalMinor || 0) - Number(invoice.paidMinor || 0));
}

export function canRetryInvoice(invoice) {
  return outstandingAmount(invoice) > 0 && !["canceled", "uncollectible", "refunded"].includes(String(invoice.status));
}

export function settlementLabel(type) {
  return type === "platform_fee" ? "Platform fee" : "Institute earning";
}
