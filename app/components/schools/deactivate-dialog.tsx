import * as React from 'react';
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { LoadingButton } from '@/components/ui/loading-button';

interface DeactivateTarget {
  url: string;
  name: string;
}

/**
 * The deactivation flow keeps its server-side safeguards (conflict check, effective date,
 * reason, current password). This dialog is only a lightweight confirmation step that
 * navigates to that existing confirm page.
 */
export function DeactivateDialog() {
  const [open, setOpen] = React.useState(false);
  const [target, setTarget] = React.useState<DeactivateTarget | null>(null);
  const [isNavigating, setIsNavigating] = React.useState(false);

  React.useEffect(() => {
    const handler = (event: MouseEvent) => {
      const trigger = (event.target as HTMLElement).closest<HTMLElement>('.deactivate-trigger');
      if (!trigger) {
        return;
      }

      event.preventDefault();
      setTarget({
        url: trigger.dataset.deactivateUrl || '',
        name: trigger.dataset.schoolName || '',
      });
      setOpen(true);
    };

    document.addEventListener('click', handler);
    return () => document.removeEventListener('click', handler);
  }, []);

  return (
    <AlertDialog open={open} onOpenChange={setOpen}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Deactivate this school?</AlertDialogTitle>
          <AlertDialogDescription>
            {target?.name ? `You&apos;re about to deactivate ${target.name}. ` : ''}
            On the next step you&apos;ll be asked for an effective date and a reason. Deliveries
            stop from that date, and the school&apos;s history is kept.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={isNavigating}>Cancel</AlertDialogCancel>
          <LoadingButton
            variant="destructive"
            isLoading={isNavigating}
            onClick={(e) => {
              e.preventDefault();
              if (target?.url) {
                setIsNavigating(true);
                window.location.href = target.url;
              }
            }}
          >
            Continue
          </LoadingButton>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
