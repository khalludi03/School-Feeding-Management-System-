import { Toaster as Sonner, type ToasterProps } from 'sonner';

export function Toaster(props: ToasterProps) {
  return (
    <Sonner
      position="top-right"
      richColors
      closeButton
      toastOptions={{
        classNames: {
          toast: 'bg-popover text-popover-foreground border-border rounded-md shadow-md',
        },
      }}
      {...props}
    />
  );
}

export { Toaster as SonnerToaster };
