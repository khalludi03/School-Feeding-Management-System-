import { chromium } from '@playwright/test';
import fs from 'fs';

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

    console.log('Entering fresh delivery (CH-777)...');
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
    for (const input of quantityInputs) await input.fill('0');
    
    await page.fill('input[name="quantities[1]"]', '100');
    await page.fill('input[name="allocations[1][0][quantity]"]', '100');
    await page.fill('input[name="allocations[1][0][date]"]', '2026-09-02');
    
    await page.fill('input[name="chalan_number"]', 'CH-777');
    await page.fill('input[name="chalan_date"]', '2026-09-02');
    
    await page.evaluate(() => {
      document.querySelector('input[name="variance_explanation"]').value = 'Test variance explanation';
      document.getElementById('chalan_photo_base64').value = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    });
    
    await page.click('button:has-text("Record delivery")');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('Saved! Final URL:', page.url());

    console.log('Checking My Entries for CH-777...');
    await page.goto('https://sfp-web-app-production.up.railway.app/field/my-entries');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    const thumbnails = await page.$$('img[alt="Chalan photo"]');
    console.log(`Found ${thumbnails.length} thumbnails on My Entries page.`);
    
    const correctLinks = await page.$$('a:has-text("Correct")');
    if (correctLinks.length > 0) {
      console.log('Clicking Correct on first entry...');
      await Promise.all([
        page.waitForNavigation(),
        correctLinks[0].click()
      ]);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      console.log('Edit page loaded.');
      
      const correctionReason = await page.$('input[name="correction_reason"]');
      if (correctionReason) {
        console.log('Found correction_reason input! Filling it...');
        await correctionReason.fill('Test correction reason');
        await page.click('button:has-text("Save correction")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        console.log('Saved correction! Final URL:', page.url());
      } else {
        console.log('Could not find correction_reason! Dumping HTML...');
        const html = await page.content();
        fs.writeFileSync('final-edit-dump.html', html);
      }
    } else {
      console.log('No Correct links found!');
    }
  } catch (e) {
    console.error('Error during run:', e);
  } finally {
    await browser.close();
  }
})();
