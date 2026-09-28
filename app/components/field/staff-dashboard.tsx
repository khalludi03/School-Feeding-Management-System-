import * as React from 'react';
import { ChevronRight, FileText, ListChecks, PackageOpen, ShieldOff } from 'lucide-react';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { cn } from 'cn';

export interface StaffAction {
  label: string;
  href: string;
  description: string;
  icon: 'delivery' | 'zero' | 'entries' | 'report';
  featured?: boolean;
}

interface StaffDashboardProps {
  name: string;
  todayFormatted: string;
  actions: StaffAction[];
}

const ICONS = {
  delivery: PackageOpen,
  zero: ShieldOff,
  entries: ListChecks,
  report: FileText,
} as const;

/**
 * Field Staff landing screen.
 *
 * Layout notes:
 * - 1 column on mobile, 2 on tablet, 3 on desktop.
 * - "Enter Delivery" spans two columns so the main daily task reads first, while
 *   "Daily Delivery Report" also spans two on rows two so no grid cell is left empty.
 * - Every card is h-full so cards in the same row share one height.
 */
export function StaffDashboard({ name, todayFormatted, actions }: StaffDashboardProps) {
  return (
    <div className="mx-auto w-full max-w-5xl">
      <header className="mb-8">
        <p className="text-sm font-semibold text-muted-foreground">Field Staff workspace</p>
        <h1 className="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
          Hello, {name}
        </h1>
        <p className="mt-2 text-muted-foreground">Choose where you want to go.</p>
        <p className="mt-3 text-sm text-muted-foreground">{todayFormatted}</p>
      </header>

      <div className="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
        {actions.map((action) => {
          const Icon = ICONS[action.icon];
          const featured = action.featured === true;

          return (
            <a
              key={action.label}
              href={action.href}
              aria-label={action.label}
              className={cn(
                'group h-full rounded-xl outline-none transition-all focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background active:scale-[0.99]',
                featured ? 'md:col-span-2' : 'md:col-span-1',
                action.label === 'Daily Delivery Report' && 'md:col-span-2 lg:col-span-2'
              )}
            >
              <Card
                className={cn(
                  'h-full border-border/70 transition-colors group-hover:border-primary/30 group-hover:bg-accent/40 dark:border-border dark:group-hover:border-primary/40',
                  featured && 'border-primary/20 bg-primary/[0.03]'
                )}
              >
                <CardHeader className="flex-row items-start justify-between gap-3">
                  <div className="flex items-center gap-3">
                    <span
                      className={cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-xl',
                        featured
                          ? 'bg-primary/10 text-primary dark:bg-primary/[0.08]'
                          : 'bg-muted text-foreground'
                      )}
                    >
                      <Icon className="size-5" aria-hidden="true" />
                    </span>
                    <CardTitle className="text-base font-semibold sm:text-lg">
                      {action.label}
                    </CardTitle>
                  </div>
                  <ChevronRight
                    className="mt-1 size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                    aria-hidden="true"
                  />
                </CardHeader>
                <CardContent className="pt-0">
                  <CardDescription className="text-sm leading-relaxed">
                    {action.description}
                  </CardDescription>
                </CardContent>
              </Card>
            </a>
          );
        })}
      </div>
    </div>
  );
}
