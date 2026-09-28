import React from 'react';
import { createRoot } from 'react-dom/client';
import { SchoolCombobox } from '@/components/forms/school-combobox';
import { MonthPicker } from '@/components/forms/month-picker';
import { SchoolFilters } from '@/components/schools/school-filters';
import { DeactivateDialog } from '@/components/schools/deactivate-dialog';
import { UserMenu } from '@/components/layout/user-menu';
import { ThemeToggle } from '@/components/layout/theme-toggle';
import { StaffDashboard } from '@/components/field/staff-dashboard';
import { ToasterMount } from '@/components/layout/toaster';
import { resolveTheme, startThemeListeners, subscribe } from '@/lib/theme';
import { ErrorBoundary } from './ErrorBoundary';

interface WindowData {
  __SCHOOLS_FILTERS__?: {
    indexUrl: string;
    search: string;
    union: string;
    includeInactive: boolean;
    perPage: number;
    unions: string[];
  };
  __FLASH__?: string;
}

const windowData = window as unknown as WindowData;

startThemeListeners();

const filtersTarget = document.getElementById('school-filters-root');
if (filtersTarget && windowData.__SCHOOLS_FILTERS__) {
  createRoot(filtersTarget).render(
    <ErrorBoundary>
      <SchoolFilters {...windowData.__SCHOOLS_FILTERS__} />
    </ErrorBoundary>
  );
}

const dialogTarget = document.getElementById('deactivate-dialog-root');
if (dialogTarget) {
  createRoot(dialogTarget).render(
    <ErrorBoundary>
      <DeactivateDialog />
    </ErrorBoundary>
  );
}

const userMenuTarget = document.getElementById('user-menu-root');
if (userMenuTarget) {
  const dataElement = document.getElementById('user-menu-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(userMenuTarget).render(
    <ErrorBoundary fallback={<div className="h-8 w-8 rounded-full bg-muted"></div>}>
      <UserMenu {...props} />
    </ErrorBoundary>
  );
}

const themeToggleRoots = document.querySelectorAll<HTMLElement>('[data-theme-toggle-root]');
if (themeToggleRoots.length > 1) {
  console.error(`Multiple theme toggle roots found (${themeToggleRoots.length}). Expected exactly 1.`);
  if (import.meta.env.DEV) {
    throw new Error('Multiple theme toggle roots found on page. Only one is allowed.');
  }
}

themeToggleRoots.forEach((target) => {
  createRoot(target).render(
    <ErrorBoundary fallback={null}>
      <ThemeToggle />
    </ErrorBoundary>
  );
});

const toasterTarget = document.getElementById('toaster-root');
if (toasterTarget) {
  const Toaster = () => {
    const [theme, setTheme] = React.useState(resolveTheme);
    React.useEffect(() => subscribe(() => setTheme(resolveTheme())), []);
    return <ToasterMount theme={theme} />;
  };

  createRoot(toasterTarget).render(
    <ErrorBoundary fallback={null}>
      <Toaster />
    </ErrorBoundary>
  );
}

const schoolComboboxTarget = document.getElementById('school-combobox-root');
if (schoolComboboxTarget) {
  const dataElement = document.getElementById('school-combobox-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(schoolComboboxTarget).render(
    <ErrorBoundary>
      <SchoolCombobox {...props} />
    </ErrorBoundary>
  );
}

const monthPickerTarget = document.getElementById('month-picker-root');
if (monthPickerTarget) {
  const dataElement = document.getElementById('month-picker-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(monthPickerTarget).render(
    <ErrorBoundary>
      <MonthPicker {...props} />
    </ErrorBoundary>
  );
}

const staffDashboardTarget = document.getElementById('field-staff-dashboard-root');
if (staffDashboardTarget) {
  const dataElement = document.getElementById('field-staff-dashboard-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(staffDashboardTarget).render(
    <ErrorBoundary>
      <StaffDashboard {...props} />
    </ErrorBoundary>
  );
}
