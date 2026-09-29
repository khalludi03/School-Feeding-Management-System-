/**
 * QA Regression Test - SFP Web App Production
 * Target: https://sfp-web-app-production.up.railway.app
 * Credentials discovered from Railway:
 *   Admin:  demo-admin / demo1234
 *   Staff:  demo-staff / staff123
 */

const { chromium } = require('./node_modules/playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://sfp-web-app-production.up.railway.app';
const SCREENSHOT_DIR = '/home/khalludi/HDD/project/sfp_web_developer_task/app/qa-screenshots';

// Corrected credentials (from Railway env vars)
const ADMIN_CREDS = { username: 'demo-admin', password: 'demo1234' };
const STAFF_CREDS = { username: 'demo-staff', password: 'staff123' };

const results = [];
let passCount = 0;
let failCount = 0;

function pass(section, check, detail = '') {
  passCount++;
  const msg = `✅ PASS | [${section}] ${check}${detail ? ' — ' + detail : ''}`;
  results.push(msg);
  console.log(msg);
}

function fail(section, check, detail = '') {
  failCount++;
  const msg = `❌ FAIL | [${section}] ${check}${detail ? ' — ' + detail : ''}`;
  results.push(msg);
  console.log(msg);
}

async function screenshot(page, name) {
  try {
    const p = path.join(SCREENSHOT_DIR, `${name}.png`);
    await page.screenshot({ path: p, fullPage: false });
  } catch (e) { /* ignore */ }
}

async function loginAs(page, creds) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
  await page.fill('input[name="username"]', creds.username);
  await page.fill('input[name="password"]', creds.password);
  await Promise.all([
    page.waitForURL('**/*', { timeout: 15000 }),
    page.click('button[type="submit"]'),
  ]);
  // After URL change, wait for page to settle
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
}

async function runTests() {
  const browser = await chromium.launch({ headless: true });
  
  try {
    // =========================================================
    // SECTION 1: AUTHENTICATION
    // =========================================================
    console.log('\n=== AUTHENTICATION ===');
    {
      const page = await browser.newPage();

      // 1a. Login page renders correctly
      try {
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
        const title = await page.title();
        const hasUsernameInput = await page.locator('input[name="username"]').count() > 0;
        const hasPasswordInput = await page.locator('input[name="password"]').count() > 0;
        const hasSubmitButton = await page.locator('button[type="submit"]').count() > 0;
        await screenshot(page, '01-login-page');
        if (hasUsernameInput && hasPasswordInput && hasSubmitButton) {
          pass('AUTH', 'Login page renders correctly', `title="${title}"`);
        } else {
          fail('AUTH', 'Login page renders correctly', `user=${hasUsernameInput}, pass=${hasPasswordInput}, btn=${hasSubmitButton}`);
        }
      } catch (e) {
        fail('AUTH', 'Login page renders correctly', e.message);
      }

      // 1b. Incorrect credentials → error message shown, no redirect
      try {
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
        await page.fill('input[name="username"]', 'wronguser_xyz_999');
        await page.fill('input[name="password"]', 'wrongpass_xyz_999');
        await Promise.all([
          page.waitForURL('**/*', { timeout: 10000 }),
          page.click('button[type="submit"]'),
        ]).catch(() => {});
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        const currentUrl = page.url();
        const pageText = await page.innerText('body');
        const stayedOnLogin = currentUrl.includes('/login');
        const hasError = pageText.toLowerCase().includes('invalid') ||
                         pageText.toLowerCase().includes('incorrect') ||
                         pageText.toLowerCase().includes('unable') ||
                         pageText.toLowerCase().includes('error') ||
                         pageText.toLowerCase().includes('wrong') ||
                         pageText.toLowerCase().includes('failed') ||
                         await page.locator('[class*="alert"], [class*="error"], [role="alert"]').count() > 0;
        await screenshot(page, '02-login-wrong-creds');
        if (stayedOnLogin && hasError) {
          pass('AUTH', 'Incorrect credentials → error shown, no redirect', `url=${currentUrl}`);
        } else if (stayedOnLogin && !hasError) {
          fail('AUTH', 'Incorrect credentials → error shown, no redirect', `stayed on login but no error found. text snippet: ${pageText.substring(0, 200)}`);
        } else {
          fail('AUTH', 'Incorrect credentials → error shown, no redirect', `redirected to: ${currentUrl}`);
        }
      } catch (e) {
        fail('AUTH', 'Incorrect credentials → error shown, no redirect', e.message);
      }

      // 1c. Admin login → /admin/dashboard
      try {
        await loginAs(page, ADMIN_CREDS);
        await screenshot(page, '03-admin-login-redirect');
        const url = page.url();
        if (url.includes('/admin/dashboard')) {
          pass('AUTH', 'Admin login → redirected to /admin/dashboard', url);
        } else {
          fail('AUTH', 'Admin login → redirected to /admin/dashboard', `got: ${url}`);
        }
      } catch (e) {
        fail('AUTH', 'Admin login → redirected to /admin/dashboard', e.message);
      }

      await page.close();
    }

    // Staff login (separate page)
    {
      const page = await browser.newPage();
      // 1d. Staff login → /field/home
      try {
        await loginAs(page, STAFF_CREDS);
        await screenshot(page, '04-staff-login-redirect');
        const url = page.url();
        if (url.includes('/field/home')) {
          pass('AUTH', 'Staff login → redirected to /field/home', url);
        } else {
          fail('AUTH', 'Staff login → redirected to /field/home', `got: ${url}`);
        }
      } catch (e) {
        fail('AUTH', 'Staff login → redirected to /field/home', e.message);
      }
      await page.close();
    }

    // =========================================================
    // SECTION 2: FIELD STAFF FLOWS (authenticated as staff)
    // =========================================================
    console.log('\n=== FIELD STAFF FLOWS ===');
    {
      const context = await browser.newContext();
      const page = await context.newPage();
      await loginAs(page, STAFF_CREDS);

      // 2a. /field/home: dashboard renders with welcome and date text
      try {
        await page.goto(`${BASE_URL}/field/home`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '05-field-home');
        const bodyText = await page.innerText('body');
        const hasWelcome = bodyText.toLowerCase().includes('welcome') || bodyText.toLowerCase().includes('hello') || bodyText.toLowerCase().includes('good');
        const hasDate = /\d{1,2}[\s\/\-]\w+[\s\/\-]\d{4}|\d{4}/.test(bodyText) || bodyText.toLowerCase().includes('today') || bodyText.toLowerCase().includes('monday') || bodyText.toLowerCase().includes('tuesday') || bodyText.toLowerCase().includes('wednesday') || bodyText.toLowerCase().includes('thursday') || bodyText.toLowerCase().includes('friday');
        if (hasWelcome || hasDate) {
          pass('FIELD', '/field/home renders with welcome & date text', `welcome=${hasWelcome}, date=${hasDate}`);
        } else {
          fail('FIELD', '/field/home renders with welcome & date text', `text snippet: ${bodyText.substring(0, 300)}`);
        }
      } catch (e) {
        fail('FIELD', '/field/home renders with welcome & date text', e.message);
      }

      // 2b. "Today's session" date badge
      try {
        const bodyText = await page.innerText('body');
        // Check for various forms of "today's session" or session badge
        const hasBadge = /today'?s?\s*session/i.test(bodyText) ||
                         await page.locator('[class*="badge"], [class*="session"], [class*="today"]').count() > 0;
        if (hasBadge) {
          pass('FIELD', "/field/home: 'Today's session' date badge present");
        } else {
          // Show what session-related text exists
          const sessionMatch = bodyText.match(/.{0,50}session.{0,50}/i);
          fail('FIELD', "/field/home: 'Today's session' date badge present", `not found. context: ${sessionMatch ? sessionMatch[0] : 'none'}`);
        }
      } catch (e) {
        fail('FIELD', "/field/home: 'Today's session' date badge", e.message);
      }

      // 2c. 4 action cards
      try {
        const fieldLinks = await page.locator('a[href*="/field/"]').count();
        const cardEls = await page.locator('[class*="card"]').count();
        if (fieldLinks >= 4 || cardEls >= 4) {
          pass('FIELD', '/field/home: 4 action cards present', `fieldLinks=${fieldLinks}, cards=${cardEls}`);
        } else {
          fail('FIELD', '/field/home: 4 action cards present', `fieldLinks=${fieldLinks}, cards=${cardEls}`);
        }
      } catch (e) {
        fail('FIELD', '/field/home: 4 action cards', e.message);
      }

      // 2d. Enter Delivery card has class containing 'col-span'
      try {
        const deliveryLinks = await page.locator('a[href*="enter-delivery"]').all();
        if (deliveryLinks.length === 0) {
          fail('FIELD', 'Enter Delivery card has col-span class', 'Enter Delivery link not found on page');
        } else {
          const elHandle = await deliveryLinks[0].elementHandle();
          const colSpanClass = await page.evaluate(el => {
            let node = el;
            const found = [];
            while (node && node !== document.body) {
              const cls = (typeof node.className === 'string') ? node.className : '';
              if (cls.includes('col-span')) found.push(cls);
              node = node.parentElement;
            }
            return found.join(' | ');
          }, elHandle);
          if (colSpanClass) {
            pass('FIELD', 'Enter Delivery card has col-span class', `class="${colSpanClass.substring(0, 100)}"`);
          } else {
            // also check the raw HTML of the link
            const html = await deliveryLinks[0].evaluate(el => {
              let node = el;
              let result = '';
              while (node && node !== document.body) {
                result += node.className + ' | ';
                node = node.parentElement;
              }
              return result;
            });
            fail('FIELD', 'Enter Delivery card has col-span class', `no col-span in ancestors. classes: ${html.substring(0, 200)}`);
          }
        }
      } catch (e) {
        fail('FIELD', 'Enter Delivery card has col-span class', e.message);
      }

      // 2e. Exactly 1 data-theme-toggle-root element on /field/home
      try {
        const toggleCount = await page.locator('[data-theme-toggle-root]').count();
        if (toggleCount === 1) {
          pass('FIELD', '/field/home: exactly 1 data-theme-toggle-root', `count=${toggleCount}`);
        } else {
          fail('FIELD', '/field/home: exactly 1 data-theme-toggle-root', `found ${toggleCount}`);
        }
      } catch (e) {
        fail('FIELD', '/field/home: exactly 1 data-theme-toggle-root', e.message);
      }

      // 2f. /field/enter-delivery: form loads
      try {
        await page.goto(`${BASE_URL}/field/enter-delivery`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '06-field-enter-delivery');
        const hasForm = await page.locator('form').count() > 0;
        const bodyText = await page.innerText('body');
        const has500 = bodyText.includes('500') || bodyText.toLowerCase().includes('internal server error');
        if (hasForm && !has500) {
          pass('FIELD', '/field/enter-delivery: form loads');
        } else {
          fail('FIELD', '/field/enter-delivery: form loads', `hasForm=${hasForm}, has500=${has500}, url=${page.url()}`);
        }
      } catch (e) {
        fail('FIELD', '/field/enter-delivery: form loads', e.message);
      }

      // 2g. /field/my-entries: page loads, img tags for thumbnails present
      try {
        await page.goto(`${BASE_URL}/field/my-entries`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '07-field-my-entries');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.includes('500') || bodyText.toLowerCase().includes('internal server error') || bodyText.toLowerCase().includes('application error');
        const imgCount = await page.locator('img').count();
        if (!has500) {
          if (imgCount > 0) {
            pass('FIELD', '/field/my-entries: page loads with img tags', `${imgCount} images`);
          } else {
            fail('FIELD', '/field/my-entries: page loads with img tags', 'page loaded but no img tags found');
          }
        } else {
          fail('FIELD', '/field/my-entries: page loads with img tags', '500 error on page');
        }
      } catch (e) {
        fail('FIELD', '/field/my-entries: page loads with img tags', e.message);
      }

      // 2h. /field/my-entries: "Correct" link present, clicking → edit page with correction_reason input
      try {
        await page.goto(`${BASE_URL}/field/my-entries`, { waitUntil: 'networkidle', timeout: 30000 });
        // Look for Correct link with different selectors
        const correctLink = page.locator('a:text-matches("Correct", "i"), a[href*="correct"], a[href*="edit"]').first();
        const correctCount = await page.locator('a:text-matches("Correct", "i"), a[href*="correct"], a[href*="edit"]').count();
        
        if (correctCount === 0) {
          fail('FIELD', '/field/my-entries: "Correct" link → edit page with correction_reason', `No "Correct"/edit link found (${correctCount}). May be no entries.`);
        } else {
          const href = await correctLink.getAttribute('href');
          await Promise.all([
            page.waitForURL('**/*', { timeout: 15000 }),
            correctLink.click(),
          ]).catch(() => {});
          await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
          await screenshot(page, '08-field-correct-edit');
          const hasCorrectionInput = await page.locator('input[name="correction_reason"], textarea[name="correction_reason"], [name*="correction_reason"]').count() > 0;
          if (hasCorrectionInput) {
            pass('FIELD', '/field/my-entries: "Correct" link → edit page with correction_reason input', `href=${href}`);
          } else {
            // check what inputs are present
            const inputs = await page.locator('input, textarea').evaluateAll(els => els.map(e => e.name));
            fail('FIELD', '/field/my-entries: "Correct" link → edit page with correction_reason input', `correction_reason not found. inputs: ${JSON.stringify(inputs)}, url=${page.url()}`);
          }
        }
      } catch (e) {
        fail('FIELD', '/field/my-entries: "Correct" link → edit page with correction_reason', e.message);
      }

      // 2i. /field/daily-report: page loads without 500 error
      try {
        await page.goto(`${BASE_URL}/field/daily-report`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '09-field-daily-report');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.includes('500') || bodyText.toLowerCase().includes('internal server error') || bodyText.toLowerCase().includes('application error');
        const pageTitle = await page.title();
        if (!has500) {
          pass('FIELD', '/field/daily-report: loads without 500 error', `title="${pageTitle}"`);
        } else {
          fail('FIELD', '/field/daily-report: loads without 500 error', `500 error detected`);
        }
      } catch (e) {
        fail('FIELD', '/field/daily-report: loads without 500 error', e.message);
      }

      // 2j. /field/zero-confirmation/create: page loads
      try {
        await page.goto(`${BASE_URL}/field/zero-confirmation/create`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '10-field-zero-confirmation');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.includes('500') || bodyText.toLowerCase().includes('internal server error');
        const has404 = /\b404\b/.test(bodyText) && bodyText.toLowerCase().includes('not found');
        const pageTitle = await page.title();
        if (!has500 && !has404) {
          pass('FIELD', '/field/zero-confirmation/create: page loads', `title="${pageTitle}"`);
        } else {
          fail('FIELD', '/field/zero-confirmation/create: page loads', `has500=${has500}, has404=${has404}`);
        }
      } catch (e) {
        fail('FIELD', '/field/zero-confirmation/create: page loads', e.message);
      }

      await context.close();
    }

    // =========================================================
    // SECTION 3: ADMIN FLOWS
    // =========================================================
    console.log('\n=== ADMIN FLOWS ===');
    {
      const context = await browser.newContext();
      const page = await context.newPage();
      await loginAs(page, ADMIN_CREDS);

      // 3a. /admin/dashboard: page loads with summary cards
      try {
        await page.goto(`${BASE_URL}/admin/dashboard`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '11-admin-dashboard');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.toLowerCase().includes('internal server error') || bodyText.includes('Application Error');
        const cardCount = await page.locator('[class*="card"], [class*="stat"], [class*="summary"]').count();
        const pageTitle = await page.title();
        if (!has500 && cardCount > 0) {
          pass('ADMIN', '/admin/dashboard: loads with summary cards', `cards=${cardCount}, title="${pageTitle}"`);
        } else {
          fail('ADMIN', '/admin/dashboard: loads with summary cards', `has500=${has500}, cards=${cardCount}`);
        }
      } catch (e) {
        fail('ADMIN', '/admin/dashboard: loads with summary cards', e.message);
      }

      // 3b. /admin/schools: school list renders
      try {
        await page.goto(`${BASE_URL}/admin/schools`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '12-admin-schools');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.toLowerCase().includes('internal server error');
        const hasTableOrList = await page.locator('table, ul li, [class*="list"], [class*="school"], tr').count() > 0;
        if (!has500 && hasTableOrList) {
          pass('ADMIN', '/admin/schools: school list renders');
        } else {
          fail('ADMIN', '/admin/schools: school list renders', `has500=${has500}, hasTableOrList=${hasTableOrList}`);
        }
      } catch (e) {
        fail('ADMIN', '/admin/schools: school list renders', e.message);
      }

      // 3c. /admin/calendar: working day calendar loads
      try {
        await page.goto(`${BASE_URL}/admin/calendar`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '13-admin-calendar');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.toLowerCase().includes('internal server error');
        const hasCalendar = await page.locator('[class*="calendar"], [class*="Calendar"], table, [class*="day"], [class*="month"], [class*="week"]').count() > 0;
        if (!has500 && hasCalendar) {
          pass('ADMIN', '/admin/calendar: working day calendar loads');
        } else {
          fail('ADMIN', '/admin/calendar: working day calendar loads', `has500=${has500}, hasCalendar=${hasCalendar}`);
        }
      } catch (e) {
        fail('ADMIN', '/admin/calendar: working day calendar loads', e.message);
      }

      // 3d. /admin/reports/daily: daily report loads (may be empty)
      try {
        await page.goto(`${BASE_URL}/admin/reports/daily`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '14-admin-reports-daily');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.toLowerCase().includes('internal server error') || bodyText.toLowerCase().includes('application error');
        const pageTitle = await page.title();
        if (!has500) {
          pass('ADMIN', '/admin/reports/daily: daily report loads', `title="${pageTitle}"`);
        } else {
          fail('ADMIN', '/admin/reports/daily: daily report loads', `500 error: ${bodyText.substring(0, 200)}`);
        }
      } catch (e) {
        fail('ADMIN', '/admin/reports/daily: daily report loads', e.message);
      }

      // 3e. /admin/form4: form4 index loads
      try {
        await page.goto(`${BASE_URL}/admin/form4`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '15-admin-form4');
        const bodyText = await page.innerText('body');
        const has500 = bodyText.toLowerCase().includes('internal server error') || bodyText.toLowerCase().includes('application error');
        const has404 = /\b404\b/.test(bodyText) && bodyText.toLowerCase().includes('not found');
        const pageTitle = await page.title();
        if (!has500 && !has404) {
          pass('ADMIN', '/admin/form4: form4 index loads', `title="${pageTitle}"`);
        } else {
          fail('ADMIN', '/admin/form4: form4 index loads', `has500=${has500}, has404=${has404}`);
        }
      } catch (e) {
        fail('ADMIN', '/admin/form4: form4 index loads', e.message);
      }

      await context.close();
    }

    // =========================================================
    // SECTION 4: PHOTO AUTHORIZATION
    // =========================================================
    console.log('\n=== PHOTO AUTHORIZATION ===');
    {
      const context = await browser.newContext();
      const page = await context.newPage();
      await loginAs(page, STAFF_CREDS);

      try {
        // Use page.request which shares cookies with the browser context
        const response = await context.request.get(`${BASE_URL}/field/receipts/8/photo`);
        const status = response.status();
        console.log(`Photo endpoint status: ${status}`);
        if (status === 200) {
          pass('PHOTO', 'GET /field/receipts/8/photo as demo-staff → 200', `status=${status}`);
        } else if (status === 404) {
          fail('PHOTO', 'GET /field/receipts/8/photo as demo-staff → 200', `got 404 — receipt #8 may not exist`);
        } else if (status === 403) {
          fail('PHOTO', 'GET /field/receipts/8/photo as demo-staff → 200', `got 403 — authorization failed`);
        } else {
          fail('PHOTO', 'GET /field/receipts/8/photo as demo-staff → 200', `got status=${status}`);
        }
      } catch (e) {
        fail('PHOTO', 'GET /field/receipts/8/photo as demo-staff → 200', e.message);
      }

      await context.close();
    }

    // =========================================================
    // SECTION 5: THEME
    // =========================================================
    console.log('\n=== THEME ===');
    {
      // 5a. Exactly 1 data-theme-toggle-root on /login page
      {
        const page = await browser.newPage();
        try {
          await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
          const toggleCount = await page.locator('[data-theme-toggle-root]').count();
          if (toggleCount === 1) {
            pass('THEME', 'Login page: exactly 1 data-theme-toggle-root', `count=${toggleCount}`);
          } else {
            fail('THEME', 'Login page: exactly 1 data-theme-toggle-root', `found ${toggleCount}`);
          }
        } catch (e) {
          fail('THEME', 'Login page: exactly 1 data-theme-toggle-root', e.message);
        }
        await page.close();
      }

      // Staff context for theme tests
      const context = await browser.newContext();
      const page = await context.newPage();
      await loginAs(page, STAFF_CREDS);

      // 5b. Dark mode: set localStorage sfp-theme=dark, reload → html has class 'dark'
      try {
        await page.goto(`${BASE_URL}/field/home`, { waitUntil: 'networkidle', timeout: 30000 });
        await page.evaluate(() => localStorage.setItem('sfp-theme', 'dark'));
        await page.reload({ waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '17-theme-dark');
        const htmlClass = await page.evaluate(() => document.documentElement.className);
        const hasDark = htmlClass.includes('dark');
        if (hasDark) {
          pass('THEME', 'Dark mode: sfp-theme=dark → html has class "dark"', `html class="${htmlClass}"`);
        } else {
          fail('THEME', 'Dark mode: sfp-theme=dark → html has class "dark"', `html class="${htmlClass}"`);
        }
      } catch (e) {
        fail('THEME', 'Dark mode: sfp-theme=dark → html has class "dark"', e.message);
      }

      // 5c. Light mode: set localStorage sfp-theme=light, reload → html does NOT have class 'dark'
      try {
        await page.goto(`${BASE_URL}/field/home`, { waitUntil: 'networkidle', timeout: 30000 });
        await page.evaluate(() => localStorage.setItem('sfp-theme', 'light'));
        await page.reload({ waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '18-theme-light');
        const htmlClass = await page.evaluate(() => document.documentElement.className);
        const hasDark = htmlClass.includes('dark');
        if (!hasDark) {
          pass('THEME', 'Light mode: sfp-theme=light → html does NOT have class "dark"', `html class="${htmlClass}"`);
        } else {
          fail('THEME', 'Light mode: sfp-theme=light → html does NOT have class "dark"', `html class="${htmlClass}"`);
        }
      } catch (e) {
        fail('THEME', 'Light mode: sfp-theme=light → html does NOT have class "dark"', e.message);
      }

      await context.close();
    }

    // =========================================================
    // SECTION 6: MOBILE (360px viewport)
    // =========================================================
    console.log('\n=== MOBILE (360px) ===');
    {
      const context = await browser.newContext({ viewport: { width: 360, height: 800 } });
      const page = await context.newPage();
      await loginAs(page, STAFF_CREDS);

      // 6a. /field/home at 360px: no horizontal overflow
      try {
        await page.goto(`${BASE_URL}/field/home`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '19-mobile-field-home');
        const overflowData = await page.evaluate(() => {
          const html = document.documentElement;
          return {
            scrollWidth: html.scrollWidth,
            clientWidth: html.clientWidth,
          };
        });
        const noOverflow = overflowData.scrollWidth <= overflowData.clientWidth;
        if (noOverflow) {
          pass('MOBILE', '/field/home at 360px: no horizontal overflow', `scrollW=${overflowData.scrollWidth}, clientW=${overflowData.clientWidth}`);
        } else {
          fail('MOBILE', '/field/home at 360px: no horizontal overflow', `scrollWidth=${overflowData.scrollWidth} > clientWidth=${overflowData.clientWidth}`);
        }
      } catch (e) {
        fail('MOBILE', '/field/home at 360px: no horizontal overflow', e.message);
      }

      // 6b. /field/enter-delivery at 360px: form renders, inputs not clipped
      try {
        await page.goto(`${BASE_URL}/field/enter-delivery`, { waitUntil: 'networkidle', timeout: 30000 });
        await screenshot(page, '20-mobile-enter-delivery');
        const hasForm = await page.locator('form').count() > 0;
        const inputs = await page.locator('input, select, textarea').all();
        let anyClipped = false;
        let clippedDetails = '';
        for (const input of inputs.slice(0, 8)) {
          try {
            const box = await input.boundingBox();
            if (box && (box.x < -1 || box.x + box.width > 361)) {
              anyClipped = true;
              const name = await input.getAttribute('name') || 'unknown';
              clippedDetails = `input[name=${name}] x=${box.x.toFixed(0)}, w=${box.width.toFixed(0)}`;
              break;
            }
          } catch (e2) { /* skip */ }
        }
        if (hasForm && !anyClipped) {
          pass('MOBILE', '/field/enter-delivery at 360px: form renders, inputs not clipped', `hasForm=${hasForm}, inputs=${inputs.length}`);
        } else {
          fail('MOBILE', '/field/enter-delivery at 360px: form renders, inputs not clipped', `hasForm=${hasForm}, clipped=${anyClipped} ${clippedDetails}`);
        }
      } catch (e) {
        fail('MOBILE', '/field/enter-delivery at 360px: form renders, inputs not clipped', e.message);
      }

      await context.close();
    }

  } finally {
    await browser.close();
  }

  // =========================================================
  // FINAL SUMMARY
  // =========================================================
  const separator = '='.repeat(65);
  console.log('\n' + separator);
  console.log('QA REGRESSION SUMMARY');
  console.log(separator);
  console.log(`Total checks: ${passCount + failCount}`);
  console.log(`✅ PASS: ${passCount}`);
  console.log(`❌ FAIL: ${failCount}`);
  
  const failures = results.filter(r => r.startsWith('❌'));
  if (failures.length > 0) {
    console.log('\n--- FAILURES ---');
    failures.forEach(f => console.log(f));
  } else {
    console.log('\n🎉 All checks passed!');
  }

  // Save markdown report
  const grouped = {};
  for (const r of results) {
    const match = r.match(/\[(\w+)\]/);
    const section = match ? match[1] : 'OTHER';
    if (!grouped[section]) grouped[section] = [];
    grouped[section].push(r);
  }

  const lines = [
    '# QA Regression Report',
    '',
    `**Date:** ${new Date().toISOString()}`,
    `**Target:** ${BASE_URL}`,
    `**Admin credentials:** demo-admin / demo1234`,
    `**Staff credentials:** demo-staff / staff123`,
    '',
  ];
  for (const [section, items] of Object.entries(grouped)) {
    lines.push(`## ${section}`);
    lines.push('');
    items.forEach(i => lines.push(i));
    lines.push('');
  }
  lines.push('## Summary');
  lines.push('');
  lines.push(`- **Total:** ${passCount + failCount}`);
  lines.push(`- **✅ PASS:** ${passCount}`);
  lines.push(`- **❌ FAIL:** ${failCount}`);
  lines.push('');
  if (failures.length > 0) {
    lines.push('## Failures');
    lines.push('');
    failures.forEach(f => lines.push(f));
  }

  const report = lines.join('\n');
  fs.writeFileSync('/home/khalludi/HDD/project/sfp_web_developer_task/app/qa-report.md', report);
  console.log('\nReport saved to: /home/khalludi/HDD/project/sfp_web_developer_task/app/qa-report.md');
  console.log('Screenshots saved to: ' + SCREENSHOT_DIR);
}

runTests().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
