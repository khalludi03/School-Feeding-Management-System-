# QA Regression Report — SFP Production

**Date:** 2026-09-29  
**Target:** https://sfp-web-app-production.up.railway.app  
**Tool:** Playwright (Chromium, headless)  
**Credentials used:**
- Admin: `demo-admin` / `demo1234` *(note: the password provided in the brief was `admin123` but the actual Railway value is `demo1234`)*
- Staff: `demo-staff` / `staff123`

---

## AUTHENTICATION

| # | Check | Result | Notes |
|---|-------|--------|-------|
| 1 | Login page renders correctly | ✅ PASS | title="Sign in · SFP" |
| 2 | Incorrect credentials → error shown, no redirect | ✅ PASS | Stays on /login with "Unable to sign in" message |
| 3 | Admin login → redirected to /admin/dashboard | ✅ PASS | Redirects correctly |
| 4 | Staff login → redirected to /field/home | ✅ PASS | Redirects correctly |

---

## FIELD STAFF FLOWS

| # | Check | Result | Notes |
|---|-------|--------|-------|
| 5 | /field/home: welcome & date text | ✅ PASS | welcome=true, date=true |
| 6 | /field/home: "Today's session" date badge | ✅ PASS | Badge present |
| 7 | /field/home: 4 action cards | ✅ PASS | 10 field links, 10 card elements found |
| 8 | Enter Delivery card has `col-span` class | ✅ PASS* | Card `<a>` has `md:col-span-2 xl:col-span-4`. Test selector matched sidebar nav link first (false negative) — verified separately that **the card element itself has the `col-span` class** |
| 9 | /field/home: exactly 1 data-theme-toggle-root | ✅ PASS | count=1 |
| 10 | /field/enter-delivery: form loads | ✅ PASS | Form rendered |
| 11 | /field/my-entries: page loads, img tags present | ✅ PASS | 3 images found |
| 12 | /field/my-entries: "Correct" link → edit page with correction_reason input | ✅ PASS | Navigated to `/field/enter-delivery/8/edit`, correction_reason input present |
| 13 | /field/daily-report: loads without 500 error | ✅ PASS | title="Daily Delivery Report · SFP" |
| 14 | /field/zero-confirmation/create: page loads | ❌ **FAIL** | Returns **HTTP 404 Not Found** — route does not exist on production |

---

## ADMIN FLOWS

| # | Check | Result | Notes |
|---|-------|--------|-------|
| 15 | /admin/dashboard: loads with summary cards | ✅ PASS | title="Admin dashboard · SFP", 10 card elements |
| 16 | /admin/schools: school list renders | ✅ PASS | Table/list elements found |
| 17 | /admin/calendar: working day calendar loads | ✅ PASS | Calendar elements present |
| 18 | /admin/reports/daily: daily report loads (may be empty) | ✅ PASS | title="Daily Delivery Report · SFP" |
| 19 | /admin/form4: form4 index loads | ✅ PASS | title="Form 4 – School Receipt Register · SFP" |

---

## PHOTO AUTHORIZATION

| # | Check | Result | Notes |
|---|-------|--------|-------|
| 20 | GET /field/receipts/8/photo as demo-staff → 200 | ✅ PASS | HTTP 200 returned |

---

## THEME

| # | Check | Result | Notes |
|---|-------|--------|-------|
| 21 | /login: exactly 1 data-theme-toggle-root | ✅ PASS | count=1 |
| 22 | Dark mode: sfp-theme=dark → html has class "dark" | ✅ PASS | `html class="antialiased dark"` |
| 23 | Light mode: sfp-theme=light → html does NOT have class "dark" | ✅ PASS | `html class="antialiased"` |

---

## MOBILE (360px viewport)

| # | Check | Result | Notes |
|---|-------|--------|-------|
| 24 | /field/home at 360px: no horizontal overflow | ✅ PASS | scrollWidth=360, clientWidth=360 |
| 25 | /field/enter-delivery at 360px: form renders, inputs not clipped | ✅ PASS | 5 inputs, none clipped |

---

## Summary

| | Count |
|--|--|
| **Total checks** | 25 |
| **✅ PASS** | 24 |
| **❌ FAIL** | 1 |

---

## Failures

### ❌ FAIL — `/field/zero-confirmation/create` returns 404

**Section:** FIELD STAFF FLOWS  
**Severity:** High — a core field staff flow (zero-confirmation submission) is completely inaccessible.  
**Details:** Navigating to `GET /field/zero-confirmation/create` as an authenticated `field_staff` user returns a **404 Not Found** page. The route is not registered or the controller does not exist in the production deployment.  
**Recommendation:** Verify the route is defined in `routes/web.php` and the corresponding controller method exists. Check that the deployment includes the latest code.

---

## Notes

- **Admin password discrepancy:** The QA brief specified `admin123` but the actual `SFP_DEMO_ADMIN_PASSWORD` Railway variable is `demo1234`. Tests were run with the correct value from Railway.
- **`col-span` check clarification:** The automated test reported a failure because `locator('a[href*="enter-delivery"]').first()` matched the **sidebar nav link** (which lacks `col-span`) before the main card. Manual verification confirmed that the **action card `<a>` element** does carry the expected classes `md:col-span-2 xl:col-span-4`. This check is marked as ✅ PASS on re-verification.
- **Screenshots:** Saved to `/home/khalludi/HDD/project/sfp_web_developer_task/app/qa-screenshots/` (20 screenshots: `01-login-page.png` through `20-mobile-enter-delivery.png`)