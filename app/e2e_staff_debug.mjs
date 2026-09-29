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
  await page.waitForTimeout(3000);

  console.log('Entering CH-555...');
  await page.goto('https://sfp-web-app-production.up.railway.app/field/enter-delivery');
  await page.waitForTimeout(2000);

  await page.fill('input[name="delivery_date"]', '2026-09-02');
  await page.click('button:has-text("Load")');
  await page.waitForTimeout(2000);
  
  await page.click('button[role="combobox"]');
  await page.fill('[cmdk-input]', 'AN-002');
  await page.click('[cmdk-item]:has-text("AN-002")');
  await page.waitForTimeout(2000);

  const inputs = await page.$$('input[type="number"][name*="[quantity]"]');
  for (const input of inputs) {
    await input.fill('100');
  }
  
  await page.fill('input[name="chalan_number"]', 'CH-555');
  
  // Set the VISIBLE file input specifically
  await page.setInputFiles('input[type="file"]:not(.hidden)', 'dummy.jpg');
  await page.waitForTimeout(1000);
  
  await page.click('button:has-text("Record delivery")');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  console.log('Saved CH-555! Final URL:', page.url());
  await page.screenshot({ path: 'field-submit-result.png', fullPage: true });

  await browser.close();
})();
