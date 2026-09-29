import { api } from "@/lib/api";
import type { AuthUser } from "@/lib/api";
import type { UiInviteActivation } from "@/data/models/parent";
import { mapInvite, mapParentStudent } from "@/data/mappers/parentMapper";

export const parentRepository = {
  async students() { return (await api<{ data: unknown[] }>("/parent-students")).data.map(mapParentStudent); },
  async invite(type: "parent" | "institute" | "educator", token: string) {
    const path = type === "parent" ? `/parent-invites/${encodeURIComponent(token)}` : type === "educator" ? `/educator-invites/${encodeURIComponent(token)}` : `/institute-invites/${encodeURIComponent(token)}`;
    return mapInvite((await api<{ data: unknown }>(path)).data, type);
  },
  activateParent(token: string, payload: UiInviteActivation) {
    return api<{ data: AuthUser; token: string }>(`/parent-invites/${encodeURIComponent(token)}/activate`, { method: "POST", body: JSON.stringify(payload) });
  },
  activateInstitute(token: string, payload: UiInviteActivation) {
    return api<{ data: AuthUser; token: string }>("/institute-invites/accept", { method: "POST", body: JSON.stringify({ token, ...payload }) });
  },
  activateEducator(token: string, payload: UiInviteActivation) {
    return api<{ data: AuthUser; token: string }>(`/educator-invites/${encodeURIComponent(token)}/accept`, { method: "POST", body: JSON.stringify(payload) });
  },
};
