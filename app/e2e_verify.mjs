import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
    await page.goto('https://sfp-web-app-production.up.railway.app/login');
    await page.fill('input[name="username"]', 'demo-staff');
    await page.fill('input[name="password"]', 'staff123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    // Edit CH-555
    await page.goto('https://sfp-web-app-production.up.railway.app/field/my-entries');
    await page.waitForTimeout(2000);
    
    const correctLinks = await page.$$('a:has-text("Correct")');
    if (correctLinks.length > 0) {
      await correctLinks[0].click();
      await page.waitForTimeout(2000);
      
      console.log('Edit page loaded.');
      
      // Submit edit
      await page.fill('input[name="correction_reason"]', 'Test correction reason');
      await page.click('button:has-text("Save changes")');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      console.log('Saved edit! Final URL:', page.url());
    } else {
      console.log('No Correct links found!');
    }
  } catch (e) {
    console.error(e);
  } finally {
    await browser.close();
  }
})();
