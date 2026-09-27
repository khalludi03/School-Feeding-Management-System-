import React from 'react';
import { createRoot } from 'react-dom/client';
import { DashboardCards } from '@/components/dashboard/dashboard-cards';

interface WindowData {
  totalDemand: number;
  totalAllocated: number;
  totalShortfall: number;
  confirmedSchoolsCount: number;
  pendingCount: number;
  totalSchools: number;
  itemCount: number;
  shortfallPct: number;
  confirmedShortfalls: Array<{
    school_code: string;
    school_name: string;
    item_key: string;
    item_name: string;
    demand: number;
    delivered: number;
    shortfall: number;
  }>;
  missingSubmissions: Array<{
    school_code: string;
    school_name: string;
    items: Array<{ item_key: string; item_name: string; demand: number }>;
  }>;
}

const target = document.getElementById('dashboard-root');

if (target) {
  const data = (window as unknown as { __DASHBOARD_DATA__: WindowData }).__DASHBOARD_DATA__;
  createRoot(target).render(
    <DashboardCards
      totalDemand={data.totalDemand}
      totalAllocated={data.totalAllocated}
      totalShortfall={data.totalShortfall}
      confirmedSchoolsCount={data.confirmedSchoolsCount}
      pendingCount={data.pendingCount}
      totalSchools={data.totalSchools}
      itemCount={data.itemCount}
      shortfallPct={data.shortfallPct}
      confirmedShortfalls={data.confirmedShortfalls}
      missingSubmissions={data.missingSubmissions}
    />
  );
}
