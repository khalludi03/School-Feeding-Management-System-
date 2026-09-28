/**
 * Single source of truth for the app's light/dark theme.
 *
 * This is deliberately framework-agnostic: the shared Laravel layout renders several
 * independent React islands (filters, deactivate dialog, user menu, toaster), each with
 * its own createRoot(). Because they are separate trees they cannot share React context,
 * so every island subscribes here instead and re-renders on change.
 *
 * Storage key  : sfp-theme
 * Values       : 'light' | 'dark' (explicit user choice) | null (follow system)
 */

export type ThemeMode = 'light' | 'dark' | null; // null === follow system
export type ResolvedTheme = 'light' | 'dark';

const STORAGE_KEY = 'sfp-theme';

function readStored(): ThemeMode {
  try {
    const value = localStorage.getItem(STORAGE_KEY);
    return value === 'light' || value === 'dark' ? value : null;
  } catch {
    return null;
  }
}

function systemTheme(): ResolvedTheme {
  return typeof window !== 'undefined' &&
    window.matchMedia('(prefers-color-scheme: dark)').matches
    ? 'dark'
    : 'light';
}

let mode: ThemeMode = typeof document !== 'undefined' ? readStored() : null;
const listeners = new Set<() => void>();

function emit(): void {
  listeners.forEach((listener) => listener());
}

/** Apply the theme to the document root and persist the mode. */
function apply(): void {
  if (typeof document === 'undefined') {
    return;
  }

  const resolved = resolveTheme();
  const root = document.documentElement;

  root.classList.toggle('dark', resolved === 'dark');
  // Lets native controls (date/month pickers, scrollbars, selects) render dark too.
  root.style.colorScheme = resolved;

  emit();
}

export function getMode(): ThemeMode {
  return mode;
}

export function resolveTheme(): ResolvedTheme {
  return mode ?? systemTheme();
}

export function setMode(next: ThemeMode): void {
  mode = next;
  try {
    if (next === null) {
      localStorage.removeItem(STORAGE_KEY);
    } else {
      localStorage.setItem(STORAGE_KEY, next);
    }
  } catch {
    // Storage unavailable (private mode / disabled) - theme still applies for this page view.
  }
  apply();
}

export function toggleTheme(): void {
  setMode(resolveTheme() === 'dark' ? 'light' : 'dark');
}

/** Re-follow the OS preference and clear any saved choice. */
export function useSystemTheme(): void {
  setMode(null);
}

export function subscribe(listener: () => void): () => void {
  listeners.add(listener);
  return () => {
    listeners.delete(listener);
  };
}

/**
 * Start listening to system changes and bfcache restores. Safe to call repeatedly;
 * it installs the listeners only once and returns the current resolved theme.
 */
let started = false;
export function startThemeListeners(): void {
  if (started || typeof window === 'undefined') {
    return;
  }
  started = true;

  // Live-update while following the system.
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (mode === null) {
      apply();
    } else {
      emit();
    }
  });

  // bfcache / back-forward navigation: another tab or page may have changed the choice.
  window.addEventListener('pageshow', () => {
    const stored = readStored();
    if (stored !== mode) {
      mode = stored;
    }
    apply();
  });

  // Keep tabs in sync when the choice is changed elsewhere.
  window.addEventListener('storage', (event) => {
    if (event.key !== STORAGE_KEY) {
      return;
    }
    mode = readStored();
    apply();
  });
}
