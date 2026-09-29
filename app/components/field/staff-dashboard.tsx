import React from 'react';
import { Card, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ChevronRight, PackageCheck, Ban, ListChecks, Activity } from 'lucide-react';
import { cn } from '@/lib/utils';

// We get these from the server. As noted in the plan, because we use a view route,
// the server doesn't provide today's items/entries, so we only display what we have:
// Name and Today's Date.
interface StaffDashboardProps {
  name: string;
  todayFormatted: string;
  actions: {
    label: string;
    href: string;
    description: string;
    icon: string;
    featured?: boolean;
  }[];
}

const ICONS: Record<string, React.ElementType> = {
  delivery: PackageCheck,
  zero: Ban,
  entries: ListChecks,
  report: Activity,
};

export function StaffDashboard({ name, todayFormatted, actions }: StaffDashboardProps) {
  // Ensure "Enter Delivery" is first
  const sortedActions = [...actions].sort((a, b) => {
    if (a.featured) return -1;
    if (b.featured) return 1;
    return 0;
  });

  return (
    <div className="flex min-h-full flex-col space-y-6 sm:space-y-8">
      <div>
        <p className="text-muted-foreground text-xs font-bold tracking-widest uppercase">
          Field Staff Workspace
        </p>
        <h1 className="text-foreground mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
          Welcome back, {name}
        </h1>
        <p className="text-muted-foreground mt-2 text-sm sm:text-base">{todayFormatted}</p>
        <div className="mt-2 flex items-center gap-2">
          <span className="bg-muted text-muted-foreground inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-3.5 w-3.5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
              aria-hidden="true"
            >
              <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
              <line x1="16" x2="16" y1="2" y2="6" />
              <line x1="8" x2="8" y1="2" y2="6" />
              <line x1="3" x2="21" y1="10" y2="10" />
            </svg>
            Today&apos;s session
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:gap-5 md:grid-cols-2 xl:grid-cols-4">
        {sortedActions.map((action) => {
          const Icon = ICONS[action.icon] || PackageCheck;
          const featured = action.featured;

          return (
            <a
              key={action.href}
              href={action.href}
              className={cn(
                'group relative flex outline-none',
                featured && 'md:col-span-2 xl:col-span-4'
              )}
              aria-label={action.label}
            >
              <Card
                className={cn(
                  'border-border/70 group-hover:border-primary/30 group-hover:bg-accent/40 dark:border-border dark:group-hover:border-primary/40 flex w-full flex-col transition-colors',
                  featured && 'border-primary/30 bg-primary/[0.04]'
                )}
              >
                <CardHeader className="grid grid-cols-[auto_1fr_auto] items-start gap-4">
                  <span
                    className={cn(
                      'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                      featured
                        ? 'bg-primary/10 text-primary dark:bg-primary/[0.12] dark:text-primary'
                        : 'bg-muted text-foreground'
                    )}
                  >
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </span>

                  <div className="flex flex-col justify-start">
                    <div className="flex flex-wrap items-center gap-2">
                      <CardTitle className="text-base font-semibold sm:text-lg">
                        {action.label}
                      </CardTitle>
                      {featured && (
                        <Badge variant="default" className="h-5 px-1.5 text-[10px] uppercase">
                          Main Task
                        </Badge>
                      )}
                    </div>
                    <CardDescription className="mt-1 text-sm leading-relaxed">
                      {action.description}
                    </CardDescription>
                  </div>

                  <ChevronRight
                    className="text-muted-foreground group-hover:text-primary mt-1 h-5 w-5 shrink-0 transition-transform group-hover:translate-x-0.5"
                    aria-hidden="true"
                  />
                </CardHeader>
              </Card>
            </a>
          );
        })}
      </div>

      {/* Spacer to vertically balance the layout since we don't have Recent Entries data */}
      <div className="min-h-[4rem] flex-1" aria-hidden="true" />
    </div>
  );
}
