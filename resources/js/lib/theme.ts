import { ref } from "vue";

export type Theme = "light" | "dark";
const KEY = "aistar-theme";

export function getInitialTheme(): Theme {
  if (typeof window === "undefined") return "light";
  try {
    const stored = localStorage.getItem(KEY) as Theme | null;
    if (stored === "light" || stored === "dark") return stored;
  } catch {}
  return window.matchMedia?.("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export function applyTheme(theme: Theme) {
  if (typeof document === "undefined") return;
  const root = document.documentElement;
  root.classList.toggle("dark", theme === "dark");
  root.style.colorScheme = theme;
  try {
    localStorage.setItem(KEY, theme);
  } catch {}
}

// Module-level ref so every component calling useTheme() shares the same state
// (mirrors the source's per-component useState, which worked because there was
// only ever one <ProfileMenu> mounted at a time — a shared ref is the safer
// Vue equivalent so multiple consumers stay in sync).
const themeState = ref<Theme>(typeof window !== "undefined" ? getInitialTheme() : "light");
if (typeof window !== "undefined") applyTheme(themeState.value);

export function useTheme() {
  function setTheme(t: Theme) {
    themeState.value = t;
    applyTheme(t);
  }
  function toggle() {
    setTheme(themeState.value === "dark" ? "light" : "dark");
  }
  return { theme: themeState, setTheme, toggle };
}

// Inline script string used to prevent FOUC during SSR hydration. Not used by
// this Vue SPA build (the same script is inlined directly in index.html instead)
// but kept for parity/reference.
export const THEME_INIT_SCRIPT = `(function(){try{var k='aistar-theme';var s=localStorage.getItem(k);var t=(s==='light'||s==='dark')?s:(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');var r=document.documentElement;if(t==='dark')r.classList.add('dark');r.style.colorScheme=t;}catch(e){}})();`;
