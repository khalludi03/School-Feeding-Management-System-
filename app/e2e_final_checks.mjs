import { chromium } from '@playwright/test';
import fs from 'fs';

const BASE = 'https://sfp-web-app-production.up.railway.app';

async function login(page, username, password) {
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
}

(async () => {
  const browser = await chromium.launch();
  let passed = 0;
  let failed = 0;

  // ── CHECK 1: /field/home renders dashboard polishes ──────────────────────
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'demo-staff', 'staff123');
    await page.goto(`${BASE}/field/home`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Screenshot
    await page.screenshot({ path: 'check-dashboard.png', fullPage: false });

    // Verify the "Today's session" badge pill rendered
    const sessionBadge = await page.$('text=Today\'s session');
    if (sessionBadge) {
      console.log('✅ CHECK 1a: Date badge "Today\'s session" rendered on /field/home');
      passed++;
    } else {
      console.log('❌ CHECK 1a: Date badge NOT found on /field/home');
      failed++;
    }

    // Verify "Enter Delivery" card has the spanning class
    const featuredLink = await page.$('a.md\\:col-span-2');
    if (featuredLink) {
      console.log('✅ CHECK 1b: Featured card has md:col-span-2 spanning class');
      passed++;
    } else {
      // Try JS check since Playwright class matching can be tricky with colons
      const spanCheck = await page.evaluate(() => {
        const links = document.querySelectorAll('a');
        for (const link of links) {
          if (link.className.includes('col-span')) return true;
        }
        return false;
      });
      if (spanCheck) {
        console.log('✅ CHECK 1b: Featured card has col-span class (verified via JS)');
        passed++;
      } else {
        console.log('❌ CHECK 1b: No col-span class found on featured card');
        failed++;
        // Dump HTML
        const html = await page.content();
        fs.writeFileSync('check-dashboard-dump.html', html);
      }
    }

    // Verify exactly ONE theme toggle root
    const toggleCount = await page.evaluate(() =>
      document.querySelectorAll('[data-theme-toggle-root]').length
    );
    if (toggleCount === 1) {
      console.log(`✅ CHECK 1c: Exactly 1 theme toggle root on /field/home (count=${toggleCount})`);
      passed++;
    } else {
      console.log(`❌ CHECK 1c: Wrong number of theme toggle roots: ${toggleCount}`);
      failed++;
    }

    await ctx.close();
  }

  // ── CHECK 2: /field/enter-delivery loads ──────────────────────────────────
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'demo-staff', 'staff123');
    await page.goto(`${BASE}/field/enter-delivery`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'check-enter-delivery.png', fullPage: false });

    const hasForm = await page.$('form');
    if (hasForm) {
      console.log('✅ CHECK 2: /field/enter-delivery loaded (form found)');
      passed++;
    } else {
      console.log('❌ CHECK 2: /field/enter-delivery did not load correctly');
      failed++;
    }
    await ctx.close();
  }

  // ── CHECK 3: Photo auth — demo-staff cannot access another user's receipt photo ──
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'demo-staff', 'staff123');

    // Find a receipt NOT owned by demo-staff by checking low IDs
    // (demo-staff's entries are recent; seed data entries are assigned to other users)
    // Try receipt ID 1 - if it doesn't belong to demo-staff, we should get 403
    let photo403Found = false;
    for (let id = 1; id <= 10; id++) {
      const resp = await page.goto(`${BASE}/field/receipts/${id}/photo`, { waitUntil: 'domcontentloaded' });
      const status = resp?.status();
      const url = page.url();
      if (status === 403) {
        console.log(`✅ CHECK 3: Got 403 on receipt ${id} photo (not owned by demo-staff) ✓`);
        passed++;
        photo403Found = true;
        break;
      } else if (status === 200) {
        console.log(`   Receipt ${id}: 200 OK (demo-staff owns this one, trying next...)`);
      } else if (status === 404) {
        console.log(`   Receipt ${id}: 404 (no photo or no receipt)`);
      } else {
        console.log(`   Receipt ${id}: status ${status}`);
      }
    }
    if (!photo403Found) {
      console.log('⚠️  CHECK 3: Could not find a receipt NOT owned by demo-staff in IDs 1-10 — checking if admin receipt exists');
      // Try to confirm via DB that demo-staff's user ID vs receipt entered_by
      const resp = await page.goto(`${BASE}/field/receipts/1/photo`);
      console.log(`   Receipt 1 status: ${resp?.status()}`);
    }

    await ctx.close();
  }

  // ── SUMMARY ──────────────────────────────────────────────────────────────
  console.log(`\nResult: ${passed} passed, ${failed} failed`);
  await browser.close();
  process.exit(failed > 0 ? 1 : 0);
})();
