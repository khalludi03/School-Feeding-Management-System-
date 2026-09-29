import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  console.log('Logging in as Staff...');
  await page.goto('https://sfp-web-app-production.up.railway.app/login');
  await page.fill('input[name="username"]', 'demo-staff');
  await page.fill('input[name="password"]', 'staff123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/field/dashboard');
  
  console.log('Entering delivery...');
  await page.goto('https://sfp-web-app-production.up.railway.app/field/enter-delivery');
  
  await page.fill('input[name="delivery_date"]', '2026-09-02');
  await page.click('button:has-text("Load")');
  
  await page.waitForSelector('button[role="combobox"]');
  await page.click('button[role="combobox"]');
  
  await page.fill('[cmdk-input]', 'AN-002');
  await page.click('[cmdk-item]:has-text("AN-002")');
  
  await page.waitForTimeout(2000);
  await page.screenshot({ path: 'field-entry.png', fullPage: true });

  console.log('Filling form...');
  const inputs = await page.$$('input[type="number"][name*="[quantity]"]');
  for (const input of inputs) {
    await input.fill('100');
  }
  
  const chalans = await page.$$('input[name*="[chalan_number]"]');
  for (const input of chalans) {
    await input.fill('CH-555');
  }

  await page.setInputFiles('input[type="file"]', 'dummy.jpg');
  await page.waitForTimeout(1000);
  await page.screenshot({ path: 'field-filled.png', fullPage: true });
  
  console.log('Submitting...');
  await page.click('button:has-text("Save entry")');
  
  await page.waitForLoadState('networkidle');
  console.log('Finished. Final URL:', page.url());
  await page.screenshot({ path: 'field-success.png', fullPage: true });

  await browser.close();
})();
