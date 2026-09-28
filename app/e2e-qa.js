const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    const baseURL = 'https://sfp-web-app-production.up.railway.app';

    console.log('Testing Admin Login...');
    await page.goto(`${baseURL}/login`);
    await page.fill('input[name="username"]', 'demo-admin');
    await page.fill('input[name="password"]', 'demo1234');
    await page.click('button[type="submit"]');
    console.log("Logged in. URL is " + page.url());
    console.log('Admin Login OK');

    console.log('Testing Schools Page...');
    await page.goto(`${baseURL}/admin/schools`);
    await page.waitForSelector('text=110 results');
    console.log('110 Schools verified.');

    console.log('Testing Staff Login...');
    await page.goto(`${baseURL}/logout`, { waitUntil: 'networkidle' }); // Not a real route, just reset session. Actually let's just clear cookies.
    await page.context().clearCookies();
    await page.goto(`${baseURL}/login`);
    await page.fill('input[name="username"]', 'demo-staff');
    await page.fill('input[name="password"]', 'staff123');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/field`);
    console.log('Staff Login OK');

    console.log('Testing Enter Delivery...');
    await page.goto(`${baseURL}/field/enter-delivery`);
    await page.waitForSelector('text=Select school');
    console.log('Enter Delivery OK');

    await browser.close();
    console.log('E2E QA Pass Complete.');
})();
