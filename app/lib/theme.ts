export type ThemeMode = 'light' | 'dark' | null;
export type ResolvedTheme = 'light' | 'dark';

const STORAGE_KEY = 'sfp-theme';

// Only used for sync events or toggling, NOT initial paint resolution
function readStored(): ThemeMode {
  if (typeof localStorage === 'undefined') return null;
  try {
    const value = localStorage.getItem(STORAGE_KEY);
    return value === 'light' || value === 'dark' ? value : null;
  } catch {
    return null;
  }
}

// Initial state reads EXACTLY what the inline head script painted
let mode: ThemeMode = null;
if (typeof document !== 'undefined') {
  // If we have a stored preference, use it for logical state
  // Even if not stored, the document class is the source of truth for resolved state
  mode = readStored();
}

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
  // If a mode is set natively, trust it.
  if (mode !== null) return mode;

  // If no mode is stored but we are hydrating, read what the head script painted
  if (typeof document !== 'undefined') {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  return 'light';
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
          }
        });
      }
    }
  } catch {}

  try {
    window.addEventListener('pageshow', (event) => {
      if (event.persisted) {
        const stored = readStored();
        if (stored !== mode) {
          mode = stored;
        }
        apply();
      }
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
