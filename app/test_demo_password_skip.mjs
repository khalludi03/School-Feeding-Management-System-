import { chromium } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'https://sfp-web-app-production.up.railway.app';

(async () => {
  console.log('Setting up test users on production via Railway SSH...');
  try {
    execSync(`railway ssh "cd /app && php artisan tinker --execute=\\"DB::table('users')->whereIn('username', ['demo-admin', 'demo-staff'])->update(['must_change_password'=>true]); DB::table('users')->where('username', 'real-staff')->delete(); DB::table('users')->insert(['name'=>'Real Staff', 'username'=>'real-staff', 'password'=>Hash::make('temp1234'), 'role'=>'field_staff', 'must_change_password'=>true, 'is_demo'=>false, 'is_active'=>true, 'created_at'=>now(), 'updated_at'=>now()]);\\""`, { stdio: 'inherit' });
  } catch(e) {
    console.error('Failed to setup test users.');
    process.exit(1);
  }

  const browser = await chromium.launch();
  let failed = false;

  // Function to test login redirect
  async function testLogin(username, password, expectedUrlPartial) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    console.log(`\nLogging in as ${username}...`);
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const currentUrl = page.url();
    if (currentUrl.includes(expectedUrlPartial)) {
      console.log(`✅ ${username} successfully redirected to ${expectedUrlPartial}`);
    } else {
      console.error(`❌ ${username} failed! Expected ${expectedUrlPartial}, but got ${currentUrl}`);
      failed = true;
    }
    await ctx.close();
  }

  // 1. Test demo-admin
  await testLogin('demo-admin', 'demo1234', '/admin/dashboard');

  // 2. Test demo-staff
  await testLogin('demo-staff', 'staff123', '/field/home');

  // 3. Test real-staff
  await testLogin('real-staff', 'temp1234', '/change-temporary-password');

  console.log('\nCleaning up test users...');
  execSync(`railway ssh "cd /app && php artisan tinker --execute=\\"DB::table('users')->where('username', 'real-staff')->delete();\\""`, { stdio: 'inherit' });

  await browser.close();
  
  if (failed) {
    console.error('One or more tests failed.');
    process.exit(1);
  } else {
    console.log('All tests passed.');
  }
})();
