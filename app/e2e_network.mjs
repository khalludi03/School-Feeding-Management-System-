import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  page.on('console', msg => console.log('BROWSER:', msg.text()));

  await page.goto('https://sfp-web-app-production.up.railway.app/login');
  await page.fill('input[name="username"]', 'demo-staff');
  await page.fill('input[name="password"]', 'staff123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  await page.goto('https://sfp-web-app-production.up.railway.app/field/enter-delivery?delivery_date=2026-09-02');
  await page.waitForTimeout(2000);
  
  // Inject script to log props
  await page.evaluate(() => {
    // we can't easily grab props, but we can look at the raw HTML!
  });

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

  // Print the raw props data-page attribute from Inertia or whatever? This is NOT Inertia!
  // It's a standard Blade template rendering a React root.
  const rawProps = await page.evaluate(() => {
    const root = document.querySelector('[data-react-class="field/DeliveryForm"]');
    return root ? root.getAttribute('data-react-props') : null;
  });
  console.log('Props after redirect:', rawProps);

  await browser.close();
})();
