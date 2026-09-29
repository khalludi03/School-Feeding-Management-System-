import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  await page.goto('https://sfp-web-app-production.up.railway.app/login');
  await page.fill('input[name="username"]', 'demo-staff');
  await page.fill('input[name="password"]', 'staff123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  await page.goto('https://sfp-web-app-production.up.railway.app/field/enter-delivery');
  await page.waitForTimeout(2000);
  await page.fill('input[name="delivery_date"]', '2026-09-02');
  await page.click('button:has-text("Load")');
  await page.waitForTimeout(2000);
  
  await page.click('button[role="combobox"]');
  await page.fill('[cmdk-input]', 'AN-002');
  await page.click('[cmdk-item]:has-text("AN-002")');
  await page.waitForTimeout(2000);
  
  const fileInputs = await page.$$('input[type="file"]');
  await fileInputs[0].setInputFiles('dummy.jpg');
  await page.waitForTimeout(2000);
  
  const base64Value = await page.$eval('#chalan_photo_base64', el => el.value);
  console.log('Base64 value starts with:', base64Value.substring(0, 30));

  await browser.close();
})();
