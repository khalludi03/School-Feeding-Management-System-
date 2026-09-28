import React from 'react';
import { createRoot } from 'react-dom/client';
import { SchoolFilters } from '@/components/schools/school-filters';
import { DeactivateDialog } from '@/components/schools/deactivate-dialog';
import { UserMenu } from '@/components/layout/user-menu';
import { ToasterMount } from '@/components/layout/toaster';

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

const toasterTarget = document.getElementById('toaster-root');
if (toasterTarget) {
  createRoot(toasterTarget).render(<ToasterMount />);
}
import './loading-buttons';

import { setupDownloadButton } from './download-helper';
declare global {
    interface Window {
        setupDownloadButton: typeof setupDownloadButton;
    }
}
window.setupDownloadButton = setupDownloadButton;
