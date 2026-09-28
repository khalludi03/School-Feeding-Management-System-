import * as React from 'react';
import { ChevronDown, KeyRound, LogOut } from 'lucide-react';
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
import { startThemeListeners, subscribe } from '@/lib/theme';

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

/**
 * Navbar controls for signed-in users. The theme switch lives here so every
 * authenticated layout renders exactly one switch, placed immediately before
 * the avatar menu without depending on the surrounding markup.
 */
export function UserMenu({ name, canChangePassword, passwordUrl }: UserMenuProps) {
  // Nothing here depends on the theme, but subscribing keeps the island mounted
  // consistently alongside the toggle it renders.
  const [, forceUpdate] = React.useReducer((count: number) => count + 1, 0);

  React.useEffect(() => {
    startThemeListeners();
    return subscribe(() => forceUpdate());
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
        <DropdownMenuSeparator />
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
