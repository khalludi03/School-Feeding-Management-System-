import { chromium } from '@playwright/test';

const BASE = 'https://sfp-web-app-production.up.railway.app';

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  // Login as demo-staff
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', 'demo-staff');
  await page.fill('input[name="password"]', 'staff123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
  console.log('Logged in as demo-staff (user ID 3)');

  // Try receipt 9 — owned by demo-admin (user ID 2), NOT demo-staff
  const resp = await page.goto(`${BASE}/field/receipts/9/photo`, { waitUntil: 'domcontentloaded' });
  const status = resp?.status();
  const body = await page.content();

  console.log(`GET /field/receipts/9/photo → HTTP ${status}`);

  if (status === 403) {
    console.log('✅ PHOTO AUTH CHECK: Got 403 — demo-staff cannot access admin-owned receipt photo');
    console.log('   Authorization is working correctly.');
  } else if (status === 200) {
    console.log('❌ PHOTO AUTH CHECK: Got 200 — demo-staff CAN access a receipt they do not own! BUG!');
  } else {
    console.log(`⚠️  PHOTO AUTH CHECK: Unexpected status ${status}`);
    console.log('Body snippet:', body.slice(0, 300));
  }

  // Also confirm demo-staff CAN access their own receipt (ID 8)
  const resp2 = await page.goto(`${BASE}/field/receipts/8/photo`, { waitUntil: 'domcontentloaded' });
  const status2 = resp2?.status();
  console.log(`GET /field/receipts/8/photo → HTTP ${status2}`);
  if (status2 === 200) {
    console.log('✅ OWN RECEIPT CHECK: Got 200 — demo-staff can access their own receipt photo ✓');
  } else {
    console.log(`❌ OWN RECEIPT CHECK: Unexpected status ${status2} for own receipt`);
  }

  await browser.close();
})();
