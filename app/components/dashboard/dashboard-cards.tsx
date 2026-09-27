"use client"

import React from "react"
import {
  PackageIcon,
  CheckCircleIcon,
  AlertTriangleIcon,
  ClockIcon,
} from "lucide-react"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  TableEmpty,
} from "@/components/ui/table"

interface ShortfallRow {
  school_code: string
  school_name: string
  item_key: string
  item_name: string
  demand: number
  delivered: number
  shortfall: number
}

interface MissingSubmissionRow {
  school_code: string
  school_name: string
  items: Array<{ item_key: string; item_name: string; demand: number }>
}

interface DashboardProps {
  totalDemand: number
  totalAllocated: number
  totalShortfall: number
  confirmedSchoolsCount: number
  pendingCount: number
  totalSchools: number
  itemCount: number
  shortfallPct: number
  confirmedShortfalls: ShortfallRow[]
  missingSubmissions: MissingSubmissionRow[]
}

function NumberBlock({
  value,
  colorClass,
}: {
  value: number
  colorClass: string
}) {
  return (
    <div className={`text-3xl font-bold tabular-nums ${colorClass}`}>
      {value.toLocaleString()}
    </div>
  )
}

function SummaryCard({
  icon,
  label,
  value,
  subtext,
  colorClass,
  href,
}: {
  icon: React.ReactNode
  label: string
  value: number | string
  subtext: string
  colorClass: string
  href?: string
}) {
  const cardContent = (
    <Card
      className={href ? "cursor-pointer no-underline hover:shadow-md transition-shadow" : ""}
    >
      <CardContent className="flex flex-col gap-1 p-4">
        <div className="flex items-center justify-between">
          <span className="text-sm font-semibold text-muted-foreground">{label}</span>
          <span className="text-muted-foreground">{icon}</span>
        </div>
        {typeof value === "number" ? (
          <NumberBlock value={value} colorClass={colorClass} />
        ) : (
          <div className={`text-3xl font-bold ${colorClass}`}>{value}</div>
        )}
        <div className="label text-xs">{subtext}</div>
      </CardContent>
    </Card>
  )

  if (href) {
    return (
      <a href={href} className="no-underline">
        {cardContent}
      </a>
    )
  }

  return cardContent
}

function ShortfallBadge({ count }: { count: number }) {
  if (count === 0) {
    return <Badge variant="neutral">{count}</Badge>
  }
  return <Badge variant="destructive">{count}</Badge>
}

function MissingBadge({ count }: { count: number }) {
  if (count === 0) {
    return <Badge variant="neutral">{count}</Badge>
  }
  return <Badge variant="warning">{count}</Badge>
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
        <h2 className="text-xl font-semibold text-foreground">Today's summary</h2>
        <p className="mt-1 text-sm text-muted-foreground">
          Combined across all items and schools.
        </p>
        <div className="mt-4 grid gap-4 grid-cols-2 lg:grid-cols-4">
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
            href="#missing-submissions"
          />
        </div>
      </section>

      {/* Confirmed shortfalls + Missing submissions side by side */}
      <section className="mt-8 grid gap-6 lg:grid-cols-2">
        {/* Confirmed shortfalls */}
        <Card>
          <CardContent className="flex flex-col gap-2 p-4">
            <div className="flex items-center justify-between">
              <h3 className="text-base font-semibold text-foreground">
                Confirmed shortfalls
              </h3>
              <ShortfallBadge count={confirmedShortfalls.length} />
            </div>
            <p className="text-sm text-muted-foreground">
              Only counts schools that have already submitted today&apos;s entry
              and recorded a quantity below demand.
            </p>
            <div className="mt-2 overflow-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>School</TableHead>
                    <TableHead>Item</TableHead>
                    <TableHead className="text-right">Demand</TableHead>
                    <TableHead className="text-right">Delivered</TableHead>
                    <TableHead className="text-right">Shortfall</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {confirmedShortfalls.length === 0 ? (
                    <TableEmpty>No confirmed shortfalls today.</TableEmpty>
                  ) : (
                    confirmedShortfalls.map((row, i) => (
                      <TableRow key={i}>
                        <TableCell>
                          <div className="font-medium">{row.school_name}</div>
                          <div className="text-xs text-muted-foreground">
                            {row.school_code}
                          </div>
                        </TableCell>
                        <TableCell>{row.item_name}</TableCell>
                        <TableCell className="text-right">
                          {row.demand.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-right">
                          {row.delivered.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-right font-semibold text-destructive">
                          {row.shortfall.toLocaleString()}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>

        {/* Missing submissions */}
        <Card>
          <CardContent className="flex flex-col gap-2 p-4">
            <div className="flex items-center justify-between" id="missing-submissions">
              <h3 className="text-base font-semibold text-foreground">
                Missing submissions
              </h3>
              <MissingBadge count={missingSubmissions.length} />
            </div>
            <p className="text-sm text-muted-foreground">
              Schools that have not recorded today&apos;s expected items.
            </p>
            <div className="mt-2 overflow-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>School</TableHead>
                    <TableHead>Missing items</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {missingSubmissions.length === 0 ? (
                    <TableEmpty>All expected schools have submitted.</TableEmpty>
                  ) : (
                    missingSubmissions.map((row, i) => (
                      <TableRow key={i}>
                        <TableCell>
                          <div className="font-medium">{row.school_name}</div>
                          <div className="text-xs text-muted-foreground">
                            {row.school_code}
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="flex flex-wrap gap-1.5">
                            {row.items.map((item) => (
                              <Badge key={item.item_key} variant="outline">
                                {item.item_name} · {item.demand.toLocaleString()}
                              </Badge>
                            ))}
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </section>
    </>
  )
}
