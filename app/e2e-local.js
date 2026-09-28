const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    const baseURL = 'http://localhost:8002';

    console.log('Testing Staff Login...');
    await page.goto(`${baseURL}/login`);
    await page.fill('input[name="username"]', 'demo-staff');
    await page.fill('input[name="password"]', 'staff123');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/field`);
    console.log('Staff Login OK');

    console.log('Testing Enter Delivery...');
    await page.goto(`${baseURL}/field/enter-delivery`);
    
    // Check if the React component rendered
    await page.waitForSelector('text=Chalan number');
    console.log('Enter Delivery OK - React form mounted');

    // Select school
    await page.click('button[role="combobox"]');
    await page.waitForSelector('[role="option"]');
    await page.click('[role="option"]:first-child');
    console.log('School selected');

    // Check if item inputs show up
    await page.waitForSelector('input[name="quantities[1]"]');
    await page.fill('input[name="quantities[1]"]', '100');
    console.log('Quantity filled');

    // Fill chalan number
    await page.fill('input[name="chalan_number"]', 'CH-1234');
    
    // Submit
    await page.click('button:has-text("Record delivery")');
    await page.waitForURL(`${baseURL}/field/my-entries`);
    console.log('Submission OK!');

    await browser.close();
    console.log('Local E2E QA Pass Complete.');
})();
