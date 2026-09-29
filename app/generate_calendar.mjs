import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  console.log('Logging in as Admin...');
  await page.goto('https://sfp-web-app-production.up.railway.app/login');
  await page.fill('input[name="username"]', 'demo-admin');
  await page.fill('input[name="password"]', 'demo1234');
  await page.click('button[type="submit"]');

  await page.waitForURL('**/admin/dashboard');
  console.log('Logged in.');

  console.log('Navigating to September config...');
  await page.goto('https://sfp-web-app-production.up.railway.app/admin/calendar/2026-09/month-config');
  
  console.log('Saving configuration...');
  await page.click('button:has-text("Save configuration")');
  
  await page.waitForTimeout(3000);
  console.log('Schedule generated successfully.');
  
  await browser.close();
})();
