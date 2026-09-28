import * as React from 'react';
import { Moon, Sun } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { getMode, resolveTheme, startThemeListeners, subscribe, toggleTheme } from '@/lib/theme';

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
            ? 'hidden sm:block size-4 text-muted-foreground'
            : 'hidden sm:block size-4 text-warning'
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
            ? 'hidden sm:block size-4 text-primary'
            : 'hidden sm:block size-4 text-muted-foreground'
        }
      />
      <span className="sr-only">
        {mode === null ? 'Following system theme' : `${theme} theme selected`}
      </span>
    </div>
  );
}
