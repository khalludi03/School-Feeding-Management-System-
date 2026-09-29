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

  const quantityInputs = await page.$$('input[type="number"][name^="quantities["]');
  for (const input of quantityInputs) await input.fill('100');
  const allocInputs = await page.$$('input[type="number"][name*="[quantity]"]');
  for (const input of allocInputs) await input.fill('100');
  const dateInputs = await page.$$('input[type="date"]');
  for (const input of dateInputs) await input.fill('2026-09-02');
  await page.fill('input[name="chalan_number"]', 'CH-555');
  
  await page.evaluate(() => {
    document.getElementById('chalan_photo_base64').value = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
  });
  
  await page.click('button:has-text("Record delivery")');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  await page.goto('https://sfp-web-app-production.up.railway.app/field/dump-session');
  const sessionData = await page.content();
  console.log('Session Data:', sessionData);

  await browser.close();
})();
