const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    const baseURL = 'https://sfp-web-app-production.up.railway.app';

    console.log('--- GENERATING CALENDAR ---');
    console.log('Testing Admin Login...');
    await page.goto(`${baseURL}/login`);
    await page.fill('input[name="username"]', 'demo-admin');
    await page.fill('input[name="password"]', 'demo1234');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/admin/dashboard`);
    console.log('Admin Login OK');

    console.log('Navigating to Working Day Calendar...');
    await page.goto(`${baseURL}/admin/calendar`);
    
    // There is a link to configure month items for the current cycle
    await page.click('a:has-text("Configure items")');
    await page.waitForURL(/admin\/calendar\/\d+\/month-config\/2026-09/);
    console.log('On month config page');
    
    // Click Save configuration
    await page.click('button:has-text("Save configuration")');
    await page.waitForSelector('text=Month configuration saved');
    console.log('Calendar schedules properly saved to production!');

    console.log('--- TESTING FIELD STAFF FLOW ---');
    await context.clearCookies();
    await page.goto(`${baseURL}/login`);
    await page.fill('input[name="username"]', 'demo-staff');
    await page.fill('input[name="password"]', 'staff123');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/field/dashboard`);
    console.log('Staff Login OK');

    console.log('Testing Enter Delivery React Overhaul...');
    await page.goto(`${baseURL}/field/enter-delivery`);
    
    await page.waitForSelector('button[role="combobox"]');
    console.log('React School Combobox mounted successfully');
    await page.click('button[role="combobox"]');
    await page.waitForSelector('[role="option"]');
    await page.click('[role="option"]:first-child');
    console.log('Selected School from React Combobox');

    // Generate a dummy photo
    const imageBuffer = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', 'base64');
    fs.writeFileSync('dummy.jpg', imageBuffer);

    // Wait for the upload element
    const fileInput = await page.$('input[type="file"][accept="image/*"]');
    await fileInput.setInputFiles('dummy.jpg');
    console.log('Photo compressed and attached');

    // Fill numerical inputs
    // Assuming bread is input[name="quantities[1]"]
    await page.fill('input[name="quantities[1]"]', '100');
    console.log('Quantities filled');

    await page.fill('input[name="chalan_number"]', 'CH-REACT-999');
    await page.fill('input[name="chalan_date"]', '2026-09-29');

    await page.click('button:has-text("Record delivery")');
    await page.waitForURL(`${baseURL}/field/my-entries`);
    console.log('Form Submitted and Round-Trip OK!');

    console.log('Testing Multiple Chalans Flow...');
    await page.goto(`${baseURL}/field/enter-delivery`);
    await page.waitForSelector('button[role="combobox"]');
    await page.click('button[role="combobox"]');
    await page.waitForSelector('[role="option"]');
    await page.click('[role="option"]:first-child');
    await page.waitForSelector('text=already has an entry');
    console.log('Already entered badge logic works in React!');

    await browser.close();
    console.log('SUCCESS: Full E2E cycle complete.');
})();
