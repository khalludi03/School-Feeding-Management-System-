import * as React from 'react';
import { Calendar, ChevronRight, FileText, ListChecks, PackageOpen, ShieldOff } from 'lucide-react';
import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

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
 */
export function StaffDashboard({ name, todayFormatted, actions }: StaffDashboardProps) {
  return (
    <div className="w-full">
      <header className="mb-8 flex flex-col items-start gap-2">
        <p className="text-sm font-semibold uppercase tracking-wider text-primary">Field Staff workspace</p>
        <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
          Hello, {name}
        </h1>
        <p className="text-base text-muted-foreground mb-1">Choose where you want to go.</p>
        <Badge variant="outline" className="gap-1.5 text-muted-foreground rounded-full px-3 py-1 font-medium bg-muted/20">
          <Calendar className="size-4" />
          {todayFormatted}
        </Badge>
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
                featured 
                  ? 'md:col-span-2 lg:col-span-3' 
                  : 'md:col-span-1 lg:col-span-1'
              )}
            >
              <Card
                className={cn(
                  'flex h-full flex-col border-border/70 transition-colors group-hover:border-primary/30 group-hover:bg-accent/40 dark:border-border dark:group-hover:border-primary/40',
                  featured && 'border-primary/20 bg-primary/[0.03]'
                )}
              >
                <CardHeader className="flex-1 grid grid-cols-[auto_1fr_auto] items-start gap-4">
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
                  
                  <div className="flex flex-col gap-1 mt-0">
                    <CardTitle className="text-base font-semibold sm:text-lg">
                      {action.label}
                    </CardTitle>
                    <CardDescription className="text-sm leading-relaxed">
                      {action.description}
                    </CardDescription>
                  </div>

                  <ChevronRight
                    className="mt-1 size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                    aria-hidden="true"
                  />
                </CardHeader>
              </Card>
            </a>
          );
        })}
      </div>
    </div>
  );
}
