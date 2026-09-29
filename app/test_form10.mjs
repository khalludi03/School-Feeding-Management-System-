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

  console.log('Fetching Form 10 HTML view...');
  const res = await page.goto(`${BASE}/admin/form10/report?month=2026-09`);
  
  if (res.ok()) {
    console.log('✅ Form 10 view loaded successfully.');
    // Check if some expected text is there
    const content = await page.textContent('body');
    if (content.includes('ফরম-১০')) {
      console.log('✅ Form 10 contains expected content.');
    } else {
      console.error('❌ Form 10 is missing content!');
    }
  } else {
    console.error(`❌ Form 10 failed with status ${res.status()}`);
  }

  await browser.close();
})();
