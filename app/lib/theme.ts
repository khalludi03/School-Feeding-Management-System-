export type ThemeMode = 'light' | 'dark' | null; // null === follow system
export type ResolvedTheme = 'light' | 'dark';

const STORAGE_KEY = 'sfp-theme';

function readStored(): ThemeMode {
  if (typeof localStorage === 'undefined') return null;
  try {
    const value = localStorage.getItem(STORAGE_KEY);
    return value === 'light' || value === 'dark' ? value : null;
  } catch {
    return null;
  }
}

function systemTheme(): ResolvedTheme {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') return 'light';
  try {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  } catch {
    return 'light';
  }
}

let mode: ThemeMode = typeof document !== 'undefined' ? readStored() : null;
const listeners = new Set<() => void>();

function emit(): void {
  listeners.forEach((listener) => listener());
}

function apply(): void {
  if (typeof document === 'undefined') {
    return;
  }

  const resolved = resolveTheme();
  const root = document.documentElement;

  root.classList.toggle('dark', resolved === 'dark');
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
  if (typeof localStorage !== 'undefined') {
    try {
      if (next === null) {
        localStorage.removeItem(STORAGE_KEY);
      } else {
        localStorage.setItem(STORAGE_KEY, next);
      }
    } catch {
      // Storage unavailable
    }
  }
  apply();
}

export function toggleTheme(): void {
  setMode(resolveTheme() === 'dark' ? 'light' : 'dark');
}

export function useSystemTheme(): void {
  setMode(null);
}

export function subscribe(listener: () => void): () => void {
  listeners.add(listener);
  return () => {
    listeners.delete(listener);
  };
}

let started = false;
export function startThemeListeners(): void {
  if (started || typeof window === 'undefined') {
    return;
  }
  started = true;

  try {
    if (typeof window.matchMedia === 'function') {
      const mql = window.matchMedia('(prefers-color-scheme: dark)');
      if (typeof mql.addEventListener === 'function') {
        mql.addEventListener('change', () => {
          if (mode === null) {
            apply();
          } else {
            emit();
          }
        });
      }
    }
  } catch {}

  try {
    window.addEventListener('pageshow', () => {
      const stored = readStored();
      if (stored !== mode) {
        mode = stored;
      }
      apply();
    });
  } catch {}

  try {
    window.addEventListener('storage', (event) => {
      if (event.key !== STORAGE_KEY) {
        return;
      }
      mode = readStored();
      apply();
    });
  } catch {}
}
