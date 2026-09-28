import * as React from 'react';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';

/**
 * Shows only the server's flashed result. Because the value is set by the server in a
 * session flash and read here after a full navigation, a toast can never imply a change
 * before the server has confirmed it.
 */
export function ToasterMount({ theme }: { theme: 'light' | 'dark' }) {
  const flash = (window as unknown as { __FLASH__?: string }).__FLASH__;

  React.useEffect(() => {
    if (flash) {
      toast.success(flash);
    }
  }, [flash]);

  return <Toaster theme={theme} />;
}
