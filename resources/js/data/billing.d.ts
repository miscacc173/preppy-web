export function createBillingKey(prefix?: string): string;
export function outstandingAmount(invoice: { totalMinor: number; paidMinor: number }): number;
export function canRetryInvoice(invoice: { totalMinor: number; paidMinor: number; status: string }): boolean;
export function settlementLabel(type: "platform_fee" | "institute_earning"): string;
