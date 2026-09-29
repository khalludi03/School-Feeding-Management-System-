import { chromium } from '@playwright/test';
import fs from 'fs';

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

  await page.fill('input[name="quantities[1]"]', '100');
  await page.fill('input[name="allocations[1][0][quantity]"]', '100');
  await page.fill('input[name="allocations[1][0][date]"]', '2026-09-02');
  
  await page.fill('input[name="chalan_number"]', 'CH-555');

  await page.evaluate(() => {
    document.getElementById('chalan_photo_base64').value = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    document.querySelector('input[name="variance_explanation"]').value = 'Test variance explanation forced';
  });

  await page.click('button:has-text("Record delivery")');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  const html = await page.content();
  fs.writeFileSync('final-page-forced2.html', html);

  await browser.close();
})();
