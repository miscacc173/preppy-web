import { api } from "@/lib/api";
import { mapCalendar, mapGoals, mapNotifications } from "@/data/mappers/studentWorkspaceMapper";
import type { UiCalendarEvent, UiStudentGoals, UiStudentNotification } from "@/data/models/studentWorkspace";

const KEYS = {
  calendar: "aistar.calendar.v1",
  goals: "aistar.goals.v1",
  notifications: "aistar.notifications.v1",
} as const;

async function state() {
  return (await api<{ data: Record<string, unknown> }>("/app-state")).data || {};
}

async function save(key: string, value: unknown) {
  await api(`/app-state/${encodeURIComponent(key)}`, { method: "PUT", body: JSON.stringify({ value }) });
}

export const studentWorkspaceRepository = {
  async calendar() { return mapCalendar((await state())[KEYS.calendar]); },
  async saveCalendar(events: UiCalendarEvent[]) { await save(KEYS.calendar, events); return events; },
  async goals() { return mapGoals((await state())[KEYS.goals]); },
  async saveGoals(goals: UiStudentGoals) { await save(KEYS.goals, goals); return goals; },
  async notifications() { return mapNotifications((await state())[KEYS.notifications]); },
  async saveNotifications(items: UiStudentNotification[]) { await save(KEYS.notifications, items); return items; },
  async notify(notification: Omit<UiStudentNotification, "id" | "createdAt" | "read">) {
    const items = await this.notifications();
    const next: UiStudentNotification[] = [{ ...notification, id: `${Date.now()}`, createdAt: new Date().toISOString(), read: false }, ...items].slice(0, 100);
    await this.saveNotifications(next);
    return next;
  },
};
