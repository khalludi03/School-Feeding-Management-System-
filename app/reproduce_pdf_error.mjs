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

  const regUrl = `${BASE}/admin/form4/101?month=2026-05`;
  console.log(`Navigating to register page: ${regUrl}`);
  await page.goto(regUrl);
  
  console.log('Clicking Download PDF...');
  
  // It might be a download OR a navigation
  try {
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 10000 }),
      page.click('text=Download PDF')
    ]);
    console.log('Success! It triggered a download!');
    console.log('Filename:', download.suggestedFilename());
  } catch (e) {
    console.log('Did not trigger download, checking if it navigated to an error...');
    const body = await page.content();
    const titleMatch = body.match(/<title>([^<]+)<\/title>/);
    console.log('Page Title:', titleMatch ? titleMatch[1] : 'No title');
    
    // Check if it's a Laravel exception
    const exceptionMatch = body.match(/<div class="exception-message">\s*([^<]+)\s*<\/div>/i);
    if (exceptionMatch) {
      console.error('EXCEPTION:', exceptionMatch[1]);
    } else {
      // Just print some body text
      console.log('Body:', body.substring(0, 500));
    }
  }

  await browser.close();
})();
