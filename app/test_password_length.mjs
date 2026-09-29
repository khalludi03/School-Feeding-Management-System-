import { chromium } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'https://sfp-web-app-production.up.railway.app';

(async () => {
  console.log('Creating test user on production via Railway SSH...');
  try {
    execSync(`railway ssh "cd /app && php artisan tinker --execute=\\"DB::table('users')->where('username', 'test-pass')->delete(); DB::table('users')->insert(['name'=>'Test User', 'username'=>'test-pass', 'password'=>Hash::make('temp1234'), 'role'=>'field_staff', 'must_change_password'=>true, 'is_active'=>true, 'created_at'=>now(), 'updated_at'=>now()]);\\""`, { stdio: 'inherit' });
  } catch(e) {
    console.error('Failed to create test user.');
    process.exit(1);
  }

  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  console.log('Logging in...');
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', 'test-pass');
  await page.fill('input[name="password"]', 'temp1234');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('Current URL:', page.url());
  if (!page.url().includes('/change-temporary-password')) {
    console.error('Did not redirect to force password change page!');
    process.exit(1);
  }

  console.log('Testing 7-char password...');
  await page.fill('input[name="password"]', 'new1234'); // 7 chars
  await page.fill('input[name="password_confirmation"]', 'new1234');
  
  // To test the backend rule, we bypass HTML5 minlength
  await page.evaluate(() => {
    document.querySelectorAll('input').forEach(el => el.removeAttribute('minlength'));
  });
  
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  // Check for validation error
  const errorText = await page.locator('text=The password field must be at least 8 characters.').count();
  if (errorText > 0 || page.url().includes('/change-temporary-password')) {
    console.log('✅ 7-character password rejected correctly (backend validation working).');
  } else {
    console.error('❌ 7-character password was ACCEPTED!');
    process.exit(1);
  }

  console.log('Testing 8-char password...');
  await page.fill('input[name="password"]', 'new12345'); // 8 chars
  await page.fill('input[name="password_confirmation"]', 'new12345');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('Current URL after 8 chars:', page.url());
  if (page.url() === `${BASE}/field/home` || page.url() === `${BASE}/` || page.url().includes('home')) {
    console.log('✅ 8-character password accepted successfully.');
  } else {
    console.error('❌ 8-character password was NOT accepted. URL is:', page.url());
    process.exit(1);
  }

  console.log('Cleaning up test user...');
  execSync(`railway ssh "cd /app && php artisan tinker --execute=\\"DB::table('users')->where('username', 'test-pass')->delete();\\""`, { stdio: 'inherit' });

  await browser.close();
  console.log('All tests passed.');
})();
