import { chromium } from '@playwright/test';
import fs from 'fs';

const BASE = 'https://sfp-web-app-production.up.railway.app';

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  console.log('Logging in...');
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  // Go to schools to get a valid school ID
  await page.goto(`${BASE}/admin/schools`);
  const viewLink = await page.locator('a:has-text("View")').first().getAttribute('href');
  const schoolId = viewLink.split('/').pop();
  console.log(`Using School ID: ${schoolId}`);

  const forms = [
    { name: 'Form 4', url: `/admin/form4/${schoolId}/pdf?month=2026-09` },
    { name: 'Form 7', url: '/admin/form7/pdf?month=2026-09' },
    { name: 'Form 10', url: '/admin/form10/pdf?month=2026-09' },
    { name: 'Form 12', url: `/admin/form12/${schoolId}/pdf?month=2026-09` },
    { name: 'Form 13', url: '/admin/form13/pdf?month=2026-09' },
  ];

  for (const form of forms) {
    console.log(`Downloading ${form.name}...`);
    try {
      const response = await page.goto(`${BASE}${form.url}`);
      
      if (!response.ok()) {
        console.error(`❌ ${form.name} failed with status: ${response.status()}`);
        process.exit(1);
      }
      
      const buffer = await response.body();
      
      // Basic validation: must be a PDF and decent size
      if (!buffer.toString('utf8', 0, 5).startsWith('%PDF-')) {
        console.error(`❌ ${form.name} did not return a valid PDF!`);
        process.exit(1);
      }
      
      if (buffer.length < 5000) {
        console.error(`❌ ${form.name} PDF is too small (${buffer.length} bytes), might be blank!`);
        process.exit(1);
      }
      
      console.log(`✅ ${form.name} generated successfully (${(buffer.length / 1024).toFixed(2)} KB)`);
      fs.writeFileSync(`${form.name.replace(' ', '')}.pdf`, buffer);
    } catch (e) {
      console.error(`❌ ${form.name} threw an error:`, e.message);
      process.exit(1);
    }
  }

  await browser.close();
  console.log('All PDFs generated and verified successfully.');
})();
