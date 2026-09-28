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

// Islands are independent trees, so they all subscribe to the shared theme utility
// instead of relying on React context. startThemeListeners() installs the system
// preference and bfcache listeners once.
startThemeListeners();

const filtersTarget = document.getElementById('school-filters-root');
if (filtersTarget && windowData.__SCHOOLS_FILTERS__) {
  createRoot(filtersTarget).render(<SchoolFilters {...windowData.__SCHOOLS_FILTERS__} />);
}

const dialogTarget = document.getElementById('deactivate-dialog-root');
if (dialogTarget) {
  createRoot(dialogTarget).render(<DeactivateDialog />);
}

const userMenuTarget = document.getElementById('user-menu-root');
if (userMenuTarget) {
  const dataElement = document.getElementById('user-menu-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(userMenuTarget).render(<UserMenu {...props} />);
}

// The toggle appears wherever layout renders it, including login (no authenticated menu).
document.querySelectorAll<HTMLElement>('[data-theme-toggle-root]').forEach((target) => {
  createRoot(target).render(<ThemeToggle />);
});

const toasterTarget = document.getElementById('toaster-root');
if (toasterTarget) {
  const Toaster = () => {
    const [theme, setTheme] = React.useState(resolveTheme);

    React.useEffect(() => subscribe(() => setTheme(resolveTheme())), []);

    return <ToasterMount theme={theme} />;
  };

  createRoot(toasterTarget).render(<Toaster />);
}


const schoolComboboxTarget = document.getElementById('school-combobox-root');
if (schoolComboboxTarget) {
  const dataElement = document.getElementById('school-combobox-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(schoolComboboxTarget).render(<SchoolCombobox {...props} />);
}

const monthPickerTarget = document.getElementById('month-picker-root');
if (monthPickerTarget) {
  const dataElement = document.getElementById('month-picker-data');
  const props = dataElement?.textContent ? JSON.parse(dataElement.textContent) : {};
  createRoot(monthPickerTarget).render(<MonthPicker {...props} />);
}
