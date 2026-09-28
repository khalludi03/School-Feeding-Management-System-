import * as React from 'react';
import { ChevronDown, KeyRound, LogOut, MonitorSmartphone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getMode, startThemeListeners, subscribe, useSystemTheme } from '@/lib/theme';

interface UserMenuProps {
  name: string;
  canChangePassword: boolean;
  passwordUrl: string;
}

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

export function UserMenu({ name, canChangePassword, passwordUrl }: UserMenuProps) {
  const [mode, setMode] = React.useState(getMode);

  React.useEffect(() => {
    startThemeListeners();
    return subscribe(() => setMode(getMode()));
  }, []);

  const handleSignOut = () => {
    document.getElementById('logout-form')?.submit();
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="gap-2 px-2">
          <Avatar className="size-8">
            <AvatarFallback>{initials(name)}</AvatarFallback>
          </Avatar>
          <span className="hidden text-sm font-medium sm:inline">{name}</span>
          <ChevronDown className="size-4 opacity-60" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuLabel>{name}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        {canChangePassword && (
          <DropdownMenuItem asChild>
            <a href={passwordUrl} className="gap-2">
              <KeyRound className="size-4" />
              Change password
            </a>
          </DropdownMenuItem>
        )}
        <DropdownMenuItem
          disabled={mode === null}
          onSelect={(event) => {
            event.preventDefault();
            useSystemTheme();
          }}
          className="gap-2"
        >
          <MonitorSmartphone className="size-4" />
          Use system setting
        </DropdownMenuItem>
        <DropdownMenuItem
          onSelect={(event) => {
            event.preventDefault();
            handleSignOut();
          }}
          className="gap-2 text-destructive focus:text-destructive"
        >
          <LogOut className="size-4" />
          Sign out
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
