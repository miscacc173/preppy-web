export type SupportedCurrency = "PKR" | "GBP" | "USD" | "EUR" | "AED" | "SAR" | "CAD" | "AUD";

export const SUPPORTED_CURRENCIES: Array<{ code: SupportedCurrency; label: string }> = [
  { code: "PKR", label: "PKR — Pakistani Rupee (₨)" },
  { code: "GBP", label: "GBP — British Pound (£)" },
  { code: "USD", label: "USD — US Dollar ($)" },
  { code: "EUR", label: "EUR — Euro (€)" },
  { code: "AED", label: "AED — UAE Dirham (د.إ)" },
  { code: "SAR", label: "SAR — Saudi Riyal (ر.س)" },
  { code: "CAD", label: "CAD — Canadian Dollar (CA$)" },
  { code: "AUD", label: "AUD — Australian Dollar (A$)" },
];

export function formatMoney(minor: number, currency = "PKR"): string {
  const code = String(currency || "PKR").toUpperCase();
  try {
    return new Intl.NumberFormat(undefined, { style: "currency", currency: code }).format((minor || 0) / 100);
  } catch {
    return `${code} ${((minor || 0) / 100).toFixed(2)}`;
  }
}

export function billingIntervalLabel(interval: string): string {
  return ({ month: "Monthly", quarter: "Quarterly", year: "Yearly" } as Record<string, string>)[interval] || interval;
}

export function billingIntervalSuffix(interval: string): string {
  return ({ month: "month", quarter: "quarter", year: "year" } as Record<string, string>)[interval] || interval;
}
