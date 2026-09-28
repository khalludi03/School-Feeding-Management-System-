import React, { useEffect, useState } from 'react';
import { Sheet, SheetContent, SheetTrigger, SheetTitle, SheetHeader } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import {
  LayoutDashboard,
  FileText,
  FileSpreadsheet,
  FileBox,
  Files,
  Users,
  School,
  Calendar,
  Scale,
  CircleDollarSign,
  PackageCheck,
  Ban,
  ListChecks,
  Activity,
  Menu,
} from 'lucide-react';

export type UserRole = 'admin' | 'field_staff';

interface NavItem {
  label: string;
  href: string;
  activePathPrefix: string;
  exactPathMatch?: boolean;
  icon: React.ElementType;
}

interface NavSection {
  title: string;
  items: NavItem[];
}

const ICONS = {
  Dashboard: LayoutDashboard,
  'Daily report': Activity,
  'Daily Delivery Report': Activity,
  'Form 4 receipts': FileText,
  'Form 7 chalan totals': FileSpreadsheet,
  'Form 10 invoice': CircleDollarSign,
  'Form 12 stock': FileBox,
  'Form 13 consolidated': Files,
  'Staff accounts': Users,
  Schools: School,
  'Working day calendar': Calendar,
  'Item rations': Scale,
  'Item prices': CircleDollarSign,
  'Enter Delivery': PackageCheck,
  'Confirm Zero': Ban,
  'My Entries': ListChecks,
};

function getConfig(role: UserRole, hasCycle: boolean, firstCycleId: number | null): NavSection[] {
  if (role === 'field_staff') {
    return [
      {
        title: 'Main',
        items: [
          {
            label: 'Dashboard',
            href: '/field/home',
            activePathPrefix: '/field/home',
            exactPathMatch: true,
            icon: ICONS['Dashboard'],
          },
        ],
      },
      {
        title: 'Deliveries',
        items: [
          {
            label: 'Enter Delivery',
            href: '/field/enter-delivery',
            activePathPrefix: '/field/enter-delivery',
            icon: ICONS['Enter Delivery'],
          },
          {
            label: 'Confirm Zero',
            href: '/field/zero-confirmations/create',
            activePathPrefix: '/field/zero-confirmations',
            icon: ICONS['Confirm Zero'],
          },
          {
            label: 'My Entries',
            href: '/field/my-entries',
            activePathPrefix: '/field/my-entries',
            icon: ICONS['My Entries'],
          },
        ],
      },
      {
        title: 'Reports',
        items: [
          {
            label: 'Daily Delivery Report',
            href: '/field/daily-report',
            activePathPrefix: '/field/daily-report',
            icon: ICONS['Daily Delivery Report'],
          },
        ],
      },
    ];
  }

  // Admin config
  const adminSections: NavSection[] = [
    {
      title: 'Main',
      items: [
        {
          label: 'Dashboard',
          href: '/admin/dashboard',
          activePathPrefix: '/admin/dashboard',
          exactPathMatch: true,
          icon: ICONS['Dashboard'],
        },
        {
          label: 'Daily report',
          href: '/admin/reports/daily',
          activePathPrefix: '/admin/reports/daily',
          icon: ICONS['Daily report'],
        },
      ],
    },
    {
      title: 'Forms & reports',
      items: [
        {
          label: 'Form 4 receipts',
          href: '/admin/form4',
          activePathPrefix: '/admin/form4',
          icon: ICONS['Form 4 receipts'],
        },
        {
          label: 'Form 7 chalan totals',
          href: '/admin/form7',
          activePathPrefix: '/admin/form7',
          icon: ICONS['Form 7 chalan totals'],
        },
        {
          label: 'Form 10 invoice',
          href: '/admin/form10',
          activePathPrefix: '/admin/form10',
          icon: ICONS['Form 10 invoice'],
        },
        {
          label: 'Form 12 stock',
          href: '/admin/form12',
          activePathPrefix: '/admin/form12',
          icon: ICONS['Form 12 stock'],
        },
        {
          label: 'Form 13 consolidated',
          href: '/admin/form13',
          activePathPrefix: '/admin/form13',
          icon: ICONS['Form 13 consolidated'],
        },
      ],
    },
    {
      title: 'Administration',
      items: [
        {
          label: 'Staff accounts',
          href: '/admin/staff',
          activePathPrefix: '/admin/staff',
          icon: ICONS['Staff accounts'],
        },
        {
          label: 'Schools',
          href: '/admin/schools',
          activePathPrefix: '/admin/schools',
          icon: ICONS['Schools'],
        },
        {
          label: 'Working day calendar',
          href: '/admin/calendar',
          activePathPrefix: '/admin/calendar',
          icon: ICONS['Working day calendar'],
        },
      ],
    },
  ];

  if (hasCycle && firstCycleId !== null) {
    adminSections.push({
      title: 'Configuration',
      items: [
        {
          label: 'Item rations',
          href: `/admin/cycles/${firstCycleId}/rations`,
          activePathPrefix: '/admin/cycles/',
          icon: ICONS['Item rations'],
        }, // wait, need a better active prefix, but let's use /rations or just rely on the full path matching roughly? Actually activePathPrefix for rations is /admin/cycles/.../rations. Let's just say /rations or something. Wait, the actual blade layout uses `request()->routeIs('rations.*')`. The URL is `/admin/cycles/{cycle}/rations`.
      ],
    });
    // Let's refine admin config items for config section below
    adminSections[3].items[0] = {
      label: 'Item rations',
      href: `/admin/cycles/${firstCycleId}/rations`,
      activePathPrefix: '/rations',
      icon: ICONS['Item rations'],
    };
    adminSections[3].items.push({
      label: 'Item prices',
      href: `/admin/cycles/${firstCycleId}/prices`,
      activePathPrefix: '/prices',
      icon: ICONS['Item prices'],
    });
  }

  return adminSections;
}

function isActive(path: string, item: NavItem) {
  if (item.exactPathMatch) {
    return path === item.activePathPrefix;
  }
  // special case for rations/prices
  if (item.activePathPrefix === '/rations') return path.includes('/rations');
  if (item.activePathPrefix === '/prices') return path.includes('/prices');
  return path.startsWith(item.activePathPrefix);
}

function SidebarContent({
  role,
  hasCycle,
  firstCycleId,
  userName,
  currentPath,
}: {
  role: UserRole;
  hasCycle: boolean;
  firstCycleId: number | null;
  userName: string;
  currentPath: string;
}) {
  const sections = getConfig(role, hasCycle, firstCycleId);

  return (
    <div className="flex h-full flex-col">
      <div className="border-border flex h-16 shrink-0 items-center gap-3 border-b px-4">
        <span className="bg-primary text-primary-foreground flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold">
          SFP
        </span>
        <div className="min-w-0">
          <p className="text-muted-foreground truncate text-xs">Workspace</p>
          <p className="truncate font-semibold">{role === 'admin' ? 'Admin' : 'Field Staff'}</p>
        </div>
      </div>

      <nav className="flex-1 overflow-y-auto p-4" aria-label="Main navigation">
        <ul className="space-y-6">
          {sections.map((section) => (
            <li key={section.title}>
              <h2 className="text-muted-foreground mb-2 px-2 text-xs font-bold tracking-wider uppercase">
                {section.title}
              </h2>
              <ul className="space-y-1">
                {section.items.map((item) => {
                  const active = isActive(currentPath, item);
                  const Icon = item.icon;
                  return (
                    <li key={item.label}>
                      <a
                        href={item.href}
                        aria-current={active ? 'page' : undefined}
                        className={cn(
                          'group focus-visible:ring-primary flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors outline-none focus-visible:ring-2',
                          active
                            ? 'bg-primary/10 text-primary'
                            : 'text-foreground hover:bg-muted/80 hover:text-foreground'
                        )}
                      >
                        <Icon
                          className={cn(
                            'h-5 w-5 shrink-0 transition-colors',
                            active
                              ? 'text-primary'
                              : 'text-muted-foreground group-hover:text-foreground'
                          )}
                          aria-hidden="true"
                        />
                        {item.label}
                      </a>
                    </li>
                  );
                })}
              </ul>
            </li>
          ))}
        </ul>
      </nav>

      <div className="border-border text-muted-foreground shrink-0 border-t p-4 text-xs">
        <p>Logged in as</p>
        <p className="text-foreground truncate font-semibold">{userName}</p>
      </div>
    </div>
  );
}

export function DesktopSidebar({
  role,
  hasCycle,
  firstCycleId,
  userName,
  currentPath,
}: {
  role: string;
  hasCycle: boolean;
  firstCycleId: string | number;
  userName: string;
  currentPath: string;
}) {
  return (
    <aside className="border-border bg-card flex min-h-full w-64 flex-col border-r">
      <SidebarContent
        role={role}
        hasCycle={hasCycle}
        firstCycleId={firstCycleId}
        userName={userName}
        currentPath={currentPath}
      />
    </aside>
  );
}

export function MobileSidebar({
  role,
  hasCycle,
  firstCycleId,
  userName,
  currentPath,
}: {
  role: string;
  hasCycle: boolean;
  firstCycleId: string | number;
  userName: string;
  currentPath: string;
}) {
  const [open, setOpen] = useState(false);

  // Close on navigation
  useEffect(() => {
    setOpen(false);
  }, [currentPath]);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <button
          type="button"
          className="hover:bg-accent focus-visible:ring-primary flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors outline-none focus-visible:ring-2 lg:hidden"
          aria-label="Open sidebar"
        >
          <Menu className="text-foreground h-5 w-5" />
        </button>
      </SheetTrigger>
      <SheetContent side="left" className="w-72 p-0 text-left">
        <SheetHeader className="sr-only">
          <SheetTitle>Navigation Menu</SheetTitle>
        </SheetHeader>
        <SidebarContent
          role={role}
          hasCycle={hasCycle}
          firstCycleId={firstCycleId}
          userName={userName}
          currentPath={currentPath}
        />
      </SheetContent>
    </Sheet>
  );
}
