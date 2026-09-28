import * as React from 'react';
import { Moon, Sun } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { getMode, resolveTheme, startThemeListeners, subscribe, toggleTheme } from '@/lib/theme';

/**
 * Light/dark toggle using the shadcn Switch. Sun sits left, Moon sits right, and the
 * icon matching the active theme is highlighted. Checked === dark.
 *
 * The ~44px tall wrapper guarantees a comfortable touch target on mobile while the
 * switch itself keeps its compact 20px height.
 */
export function ThemeToggle() {
  const [theme, setTheme] = React.useState(resolveTheme);
  const [mode, setMode] = React.useState(getMode);

  React.useEffect(() => {
    startThemeListeners();
    return subscribe(() => {
      setTheme(resolveTheme());
      setMode(getMode());
    });
  }, []);

  const isDark = theme === 'dark';

  return (
    <div className="flex min-h-11 items-center gap-2" role="group" aria-label="Colour theme">
      <Sun
        aria-hidden="true"
        className={
          isDark
            ? 'size-4 text-muted-foreground'
            : 'size-4 text-warning'
        }
      />
      <Switch
        checked={isDark}
        onCheckedChange={() => toggleTheme()}
        aria-label="Toggle dark mode"
      />
      <Moon
        aria-hidden="true"
        className={
          isDark
            ? 'size-4 text-primary'
            : 'size-4 text-muted-foreground'
        }
      />
      <span className="sr-only">
        {mode === null ? 'Following system theme' : `${theme} theme selected`}
      </span>
    </div>
  );
}
