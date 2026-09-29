import { chromium } from '@playwright/test';

const BASE = 'https://sfp-web-app-production.up.railway.app';

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  console.log('Logging in...');
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('Navigating to Form 4 landing page...');
  await page.goto(`${BASE}/admin/form4`);
  
  console.log('Selecting school...');
  await page.click('#school-combobox-root button'); // Open combobox
  await page.click('div[role="option"]:has-text("আনোয়ারা অগ্রযাত্রা సপ্রাবি")'); // wait, text might be different, let's just use Keyboard
  // Better yet, bypass UI by injecting values if needed, or just type
  
  await browser.close();
})();
