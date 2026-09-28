'use client';

import React from 'react';
import { PackageIcon, CheckCircleIcon, AlertTriangleIcon, ClockIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  TableEmpty,
} from '@/components/ui/table';

interface ShortfallRow {
  school_code: string;
  school_name: string;
  item_key: string;
  item_name: string;
  demand: number;
  delivered: number;
  shortfall: number;
}

interface MissingSubmissionRow {
  school_code: string;
  school_name: string;
  items: Array<{ item_key: string; item_name: string; demand: number }>;
}

interface DashboardProps {
  totalDemand: number;
  totalAllocated: number;
  totalShortfall: number;
  confirmedSchoolsCount: number;
  pendingCount: number;
  totalSchools: number;
  itemCount: number;
  shortfallPct: number;
  confirmedShortfalls: ShortfallRow[];
  missingSubmissions: MissingSubmissionRow[];
}

function NumberBlock({ value, colorClass }: { value: number; colorClass: string }) {
  return (
    <div className={`text-3xl font-bold tabular-nums ${colorClass}`}>{value.toLocaleString()}</div>
  );
}

function SummaryCard({
  icon,
  label,
  value,
  subtext,
  colorClass,
  href,
}: {
  icon: React.ReactNode;
  label: string;
  value: number | string;
  subtext: string;
  colorClass: string;
  href?: string;
}) {
  const cardContent = (
    <Card className={href ? 'cursor-pointer no-underline transition-shadow hover:shadow-md' : ''}>
      <CardContent className="flex flex-col gap-1 p-4">
        <div className="flex items-center justify-between">
          <span className="text-muted-foreground text-sm font-semibold">{label}</span>
          <span className="text-muted-foreground">{icon}</span>
        </div>
        {typeof value === 'number' ? (
          <NumberBlock value={value} colorClass={colorClass} />
        ) : (
          <div className={`text-3xl font-bold ${colorClass}`}>{value}</div>
        )}
        <div className="label text-xs">{subtext}</div>
      </CardContent>
    </Card>
  );

  if (href) {
    return (
      <a href={href} className="no-underline">
        {cardContent}
      </a>
    );
  }

  return cardContent;
}

function ShortfallBadge({ count }: { count: number }) {
  if (count === 0) {
    return <Badge variant="neutral">{count}</Badge>;
  }
  return <Badge variant="destructive">{count}</Badge>;
}

function MissingBadge({ count }: { count: number }) {
  if (count === 0) {
    return <Badge variant="neutral">{count}</Badge>;
  }
  return <Badge variant="warning">{count}</Badge>;
}

export function DashboardCards({
  totalDemand,
  totalAllocated,
  totalShortfall,
  confirmedSchoolsCount,
  pendingCount,
  totalSchools,
  itemCount,
  shortfallPct,
  confirmedShortfalls,
  missingSubmissions,
}: DashboardProps) {
  return (
    <>
      {/* 4-card summary row */}
      <section className="mt-8">
        <h2 className="text-foreground text-xl font-semibold">Today&apos;s summary</h2>
        <p className="text-muted-foreground mt-1 text-sm">Combined across all items and schools.</p>
        <div className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
          <SummaryCard
            icon={<PackageIcon className="size-4" />}
            label="Today's demand"
            value={totalDemand}
            subtext={`${itemCount} items · ${totalSchools} schools`}
            colorClass="text-foreground"
          />
          <SummaryCard
            icon={<CheckCircleIcon className="size-4" />}
            label="Allocated"
            value={totalAllocated}
            subtext={`${shortfallPct}% of today's demand`}
            colorClass="text-success"
          />
          <SummaryCard
            icon={<AlertTriangleIcon className="size-4" />}
            label="Total shortfall (est.)"
            value={totalShortfall}
            subtext={`incl. pending submissions across ${confirmedSchoolsCount} schools`}
            colorClass="text-destructive"
          />
          <SummaryCard
            icon={<ClockIcon className="size-4" />}
            label="Pending submissions"
            value={pendingCount}
            subtext={`of ${totalSchools} schools`}
            colorClass="text-warning"
            
          />
        </div>
      </section>

    </>
  );
}
