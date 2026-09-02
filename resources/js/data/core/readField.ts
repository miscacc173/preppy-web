export type FieldPath = string | readonly string[];

function valueAt(source: unknown, path: string): unknown {
  return path.split(".").reduce<unknown>((value, key) => {
    if (value === null || typeof value !== "object") return undefined;
    return (value as Record<string, unknown>)[key];
  }, source);
}

/**
 * Reads the first available API field. Change paths in apiFieldMap.ts when an
 * API response uses different keys; Vue components never need to change.
 */
export function readField<T>(source: unknown, path: FieldPath, fallback: T): T {
  const paths = typeof path === "string" ? [path] : path;
  for (const candidate of paths) {
    const value = valueAt(source, candidate);
    if (value !== undefined && value !== null) return value as T;
  }
  return fallback;
}

export function asNumber(value: unknown, fallback = 0): number {
  const parsed = typeof value === "number" ? value : Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

export function asStringArray(value: unknown): string[] {
  return Array.isArray(value) ? value.filter((item): item is string => typeof item === "string") : [];
}
