# School Feeding Management System

A role-based application for managing school-feeding programme schools, staff, calendars, deliveries, and reports. The Laravel application is in [`app/`](app/); Railway deployment details are in [`DEPLOY.md`](DEPLOY.md).

## Tech stack

- **Backend:** PHP 8.3+, Laravel 13
- **Frontend:** React 19 and TypeScript islands in Blade, Vite 8, Tailwind CSS 4, and shadcn-style components built on Radix UI
- **Package manager/build:** Bun (the Docker build uses Bun and `bun.lock`)
- **Database:** SQLite for local development and tests; MySQL in production
- **PDF:** mPDF
- **Excel:** application `SimpleXlsxWriter`, producing `.xlsx` files with `ZipArchive`
- **Chalan-photo storage:** local private storage by default; Supabase S3-compatible storage in production
- **Deployment:** Railway, built from the root Dockerfile and served by FrankenPHP with Caddy

## Features

### Admin

- Dashboard with daily demand, delivery totals, missing submissions, and confirmed shortfalls
- Daily delivery report with upazila totals and `.xlsx` export
- Staff account creation, editing, deactivation/reactivation, password reset, audit notes, and manual WhatsApp credential handoff
- Schools directory with official EMIS codes, identity audit history, dated enrolment counts, and programme participation periods
- Working-day calendar, holidays, weekly off days, and calendar conflict review
- Per-cycle item ration and price configuration with review steps
- Forms 4, 7, 10, 12, and 13, with PDF and/or Excel exports; the report generator can bundle per-school Form 4 or Form 12 PDFs into a ZIP
- Reassign delivery-correction responsibility to an active Field Staff account, with an audit record

### Field Staff

- **Enter Delivery:** record school/date quantities, chalan details, photos, and allocations
- **Confirm Zero:** record that a scheduled item was not delivered
- **My Entries:** review entries created by or assigned to the signed-in staff member; edit permitted entries
- **Daily Delivery Report:** view the programme summary and export it to Excel

### Shared

- Role-based access and separate Admin/Field Staff navigation
- Light/dark theme with the initial theme applied before the page paints
- Password changes, temporary-password replacement, session revocation, and throttled login
- Dated demand calculations that preserve historical deliveries when enrolment or participation changes

## Known limitations and data notes

- Programme Settings and the legacy `/admin/report-generator` page are placeholders. The working form report generator is at `/admin/reports/choose`.
- The supplied work-order data does not specify egg or banana supply weekdays. Those patterns remain unconfigured, and derived demand for those items is shown as unknown rather than zero.
- The Business Rules sheet and sample report layouts were not supplied, so generated forms cannot be checked against those missing reference documents.

## Screenshots

There are no committed image screenshots. The repository includes the dashboard HTML capture used by `tests/Feature/ScreenshotTest.php`: [`app/public/screenshot-dashboard.html`](app/public/screenshot-dashboard.html).

## Prerequisites

- PHP 8.3+ with PDO SQLite for local development (PDO MySQL for local MySQL or production)
- Composer
- Bun 1.x
- PHP extensions required by the app, including `mbstring` and `zip`
- MySQL only if you choose it for local development; production uses MySQL

## Local setup

Run these commands from the repository root. If `app/.env` already exists, keep it and update the required values instead of copying over it.

```bash
cd app
composer install
bun install --frozen-lockfile
cp .env.example .env
php artisan key:generate
```

Set `SFP_ADMIN_USERNAME` and a strong `SFP_ADMIN_PASSWORD` in `.env`. The initial Admin password is temporary and must be changed at first sign-in; it expires after 48 hours.

The example environment selects SQLite. Create the database file if it does not already exist, then migrate and seed:

```bash
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan db:seed --class=GpsfpSeptember2026Seeder
```

`DatabaseSeeder` creates the initial Admin and optional demo accounts. It does **not** add schools or programme data. `GpsfpSeptember2026Seeder` adds the September 2026 cycle and supplied Anwara data, including 110 schools. Both seeders are safe to rerun; the initial Admin's existing password is not replaced.

Build frontend assets and run the app:

```bash
bun run build
php artisan serve
```

For frontend hot reload during development, run `bun run dev` in a second terminal instead of relying on the prebuilt assets.

To use local MySQL instead, set `DB_CONNECTION=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.

## Demo accounts

To seed protected demo Admin and Field Staff accounts, set `SFP_DEMO_ENABLED=true` and provide all five `SFP_DEMO_*` values in `.env` before running `php artisan db:seed`:

- `SFP_DEMO_ADMIN_USERNAME`
- `SFP_DEMO_ADMIN_PASSWORD`
- `SFP_DEMO_STAFF_USERNAME`
- `SFP_DEMO_STAFF_PASSWORD`
- `SFP_DEMO_STAFF_WHATSAPP`

The initial Admin credentials (`SFP_ADMIN_USERNAME` and `SFP_ADMIN_PASSWORD`) are still required. Demo passwords and status are protected from changes through the app. Do not put real credentials in this README or commit them.

For Admin recovery, an operator with command-line access can issue a new temporary password, displayed once and valid for 48 hours:

```bash
php artisan sfp:reset-admin USERNAME
```

## Environment variables

`app/.env.example` contains the local defaults. Configure the following values as applicable; production-specific Railway settings are in [`DEPLOY.md`](DEPLOY.md).

| Variable(s) | Purpose |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_TIMEZONE`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `APP_MAINTENANCE_*` | Application identity, URL, timezone, locales, key, and maintenance mode. Generate the key with `php artisan key:generate`. |
| `BCRYPT_ROUNDS`, `VITE_APP_NAME` | Password hashing cost and frontend app label. |
| `DB_CONNECTION`, `DB_DATABASE`, `DB_URL`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, and driver-specific `DB_*` | Database connection. Use SQLite locally or MySQL in production. `DB_URL` may provide the complete connection URL. |
| `SESSION_*` | Session driver, lifetime, encryption, cookie name/path/domain, and security settings. Production should set `SESSION_SECURE_COOKIE=true`. |
| `CACHE_STORE`, `CACHE_*`, `DB_CACHE_*` | Cache driver and optional cache-store settings. |
| `QUEUE_CONNECTION`, `DB_QUEUE_*`, `REDIS_QUEUE_*`, `SQS_*`, `BEANSTALKD_QUEUE_*`, `QUEUE_FAILED_DRIVER` | Queue connection and backend-specific options. |
| `BROADCAST_CONNECTION` | Laravel event-broadcasting driver. |
| `FILESYSTEM_DISK`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | File storage disk and S3-compatible storage configuration. Use the S3 disk for production chalan photos. |
| `LOG_*`, `PAPERTRAIL_*` | Logging channel, level, and optional logging destinations. |
| `MAIL_*` | Laravel mail transport configuration; the app does not currently send email. |
| `REDIS_*`, `MEMCACHED_*` | Optional Laravel cache/session/queue backend settings. |
| `AUTH_*` | Laravel guard/model/password-reset configuration. |
| `SFP_DATA_PATH` | Optional override for the supplied programme data directory; defaults to `app/data`. |
| `SFP_ADMIN_NAME`, `SFP_ADMIN_USERNAME`, `SFP_ADMIN_PASSWORD` | Initial Admin identity and seeding credentials. |
| `SFP_DEMO_ENABLED`, `SFP_DEMO_ADMIN_*`, `SFP_DEMO_STAFF_*` | Opt in to demo-account seeding and supply its credentials and staff WhatsApp number. |

Laravel also accepts optional backend-specific settings such as `MEMCACHED_*`, Redis queue/cache names, and database cache or queue table names; leave those at defaults unless selecting that backend.

## Running tests

```bash
cd app
php artisan test
bunx tsc --noEmit
bun run build
```

The PHPUnit configuration uses an in-memory SQLite database, so the test suite does not require or migrate the local database file.

## Project structure

- `app/` — Laravel application root
  - `app/Http/Controllers/`, `app/Models/`, `app/Services/` — HTTP and domain code
  - `database/migrations/`, `database/seeders/`, `tests/` — schema, seed data, and tests
  - `resources/views/` — Blade pages and layouts
  - `resources/js/` and `components/` — React islands and shared UI components
  - `routes/` and `config/` — routes and framework/application configuration
- `Dockerfile`, `Caddyfile`, `railway.json` — Railway image build and runtime configuration
- `DEPLOY.md` — Railway deployment instructions
