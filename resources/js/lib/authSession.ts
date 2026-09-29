import { ref } from "vue";
import { logout } from "@/lib/api";

const signingOut = ref(false);

/**
 * One logout path for menus, sidebars and settings. A full navigation clears
 * all in-memory Pinia/query/component state after the server token is revoked.
 */
export function useAuthSession() {
  async function signOut(redirectTo = "/login") {
    if (signingOut.value) return;
    signingOut.value = true;
    try {
      await logout();
    } finally {
      window.location.replace(redirectTo);
    }
  }

  return { signingOut, signOut };
}
