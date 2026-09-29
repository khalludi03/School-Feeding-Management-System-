import { chromium } from '@playwright/test';

const BASE = 'https://sfp-web-app-production.up.railway.app';

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1024, height: 768 } });
  const page = await ctx.newPage();

  console.log('Logging in as admin...');
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('Navigating to Schools directory...');
  await page.goto(`${BASE}/admin/schools?per_page=15`);
  await page.waitForLoadState('networkidle');

  // Check for the "Source:" text
  const sourceTextCount = await page.locator('text=/Source: .*/i').count();
  if (sourceTextCount === 0) {
    console.log('✅ Debug "Source:" text is successfully removed from the table.');
  } else {
    console.error(`❌ Found "Source:" text ${sourceTextCount} times on the page!`);
    process.exit(1);
  }

  // Check the sidebar position
  const sidebar = page.locator('#desktop-sidebar-root');
  const initialBox = await sidebar.boundingBox();
  console.log(`Initial sidebar Y position: ${initialBox.y}`);

  // Scroll the main content area (which is the scrollable container now)
  // The scrollable container is the flex-1 div
  const scrollableMain = page.locator('.flex.min-w-0.flex-1.flex-col.overflow-y-auto');
  await scrollableMain.evaluate((el) => el.scrollBy(0, 500));
  await page.waitForTimeout(500);

  // Re-check sidebar position
  const scrolledBox = await sidebar.boundingBox();
  console.log(`Sidebar Y position after scrolling main content: ${scrolledBox.y}`);

  if (scrolledBox.y === 0 && initialBox.y === 0) {
    console.log('✅ Sidebar remains fixed at the top (Y=0) while scrolling main content.');
  } else {
    console.error('❌ Sidebar moved! It is not fixed.');
    process.exit(1);
  }

  // Double check the whole page scroll (body shouldn't scroll)
  await page.evaluate(() => window.scrollBy(0, 500));
  await page.waitForTimeout(500);
  const scrolledBoxBody = await sidebar.boundingBox();
  console.log(`Sidebar Y position after scrolling body: ${scrolledBoxBody.y}`);
  
  if (scrolledBoxBody.y === 0) {
    console.log('✅ Body scroll prevented, sidebar remains fixed.');
  } else {
    console.error('❌ Body scrolled and moved the sidebar!');
    process.exit(1);
  }

  await browser.close();
  console.log('All layout checks passed!');
})();
