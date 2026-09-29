import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
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

    // Initialize all to 0
    const quantityInputs = await page.$$('input[type="number"][name^="quantities["]');
    for (const input of quantityInputs) await input.fill('0');
    
    // Set item 1 to 100
    await page.fill('input[name="quantities[1]"]', '100');
    await page.fill('input[name="allocations[1][0][quantity]"]', '100');
    await page.fill('input[name="allocations[1][0][date]"]', '2026-09-02');
    
    await page.fill('input[name="chalan_number"]', 'CH-555');
    await page.fill('input[name="chalan_date"]', '2026-09-02');
    
    await page.evaluate(() => {
      document.querySelector('input[name="variance_explanation"]').value = 'Test variance explanation';
      document.getElementById('chalan_photo_base64').value = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    });
    
    await page.click('button:has-text("Record delivery")');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('Saved CH-555! Final URL:', page.url());

    console.log('Entering CH-999...');
    await page.goto('https://sfp-web-app-production.up.railway.app/field/enter-delivery');
    await page.waitForTimeout(2000);

    await page.fill('input[name="delivery_date"]', '2026-09-02');
    await page.click('button:has-text("Load")');
    await page.waitForTimeout(2000);
    
    await page.click('button[role="combobox"]');
    await page.fill('[cmdk-input]', 'AN-002');
    await page.click('[cmdk-item]:has-text("AN-002")');
    await page.waitForTimeout(2000);

    const quantityInputs2 = await page.$$('input[type="number"][name^="quantities["]');
    for (const input of quantityInputs2) await input.fill('0');

    await page.fill('input[name="quantities[1]"]', '50');
    await page.fill('input[name="allocations[1][0][quantity]"]', '50');
    await page.fill('input[name="allocations[1][0][date]"]', '2026-09-02');
    
    await page.fill('input[name="chalan_number"]', 'CH-999');
    await page.fill('input[name="chalan_date"]', '2026-09-02');
    
    await page.evaluate(() => {
      document.querySelector('input[name="variance_explanation"]').value = 'Test variance explanation 2';
      document.getElementById('chalan_photo_base64').value = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    });
    
    await page.click('button:has-text("Record delivery")');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('Saved CH-999! Final URL:', page.url());

    console.log('Checking My Entries...');
    await page.goto('https://sfp-web-app-production.up.railway.app/field/my-entries');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'final-my-entries.png', fullPage: true });

  } catch (e) {
    console.error(e);
  } finally {
    await browser.close();
  }
})();
