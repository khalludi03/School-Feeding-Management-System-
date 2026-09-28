# Railway Deployment Documentation

## Architecture
The application runs on Railway using a multi-stage Dockerfile deployment to a single `sfp-web-app` service.
- **Frontend Build**: Built with Vite & `oven/bun:1` (outputs to `public/build`).
- **Backend Runtime**: `dunglas/frankenphp:php8.4` with Caddy, configured by the root `Caddyfile`; FrankenPHP serves `app/public` and listens on Railway's `PORT`.
- **Database**: A private Railway MySQL service (`mysql.railway.internal`).
- **File Storage**: Supabase S3 is used for ephemeral-safe file uploads (`chalan_photo`).

## Environment Variables

| Variable | Meaning |
|---|---|
| `APP_NAME` | The application name (e.g. "School Feeding Management") |
| `APP_ENV` | Application environment (must be `production`) |
| `APP_DEBUG` | Whether to show detailed error pages (must be `false`) |
| `APP_KEY` | Laravel encryption key |
| `APP_URL` | The public Railway URL for proper asset & route generation |
| `DB_CONNECTION` | Database driver (must be `mysql`) |
| `DB_URL` | Complete MySQL connection string (references the internal private Railway MySQL host) |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | Set to `database` so state isn't lost on deploy |
| `SESSION_SECURE_COOKIE` | Enforces HTTPS-only cookies (`true` in production) |
| `LOG_CHANNEL` | Must be `stderr` to pipe logs to Railway's dashboard |
| `FILESYSTEM_DISK` | Set to `s3` for persistent chalan-photo uploads |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL`, `AWS_USE_PATH_STYLE_ENDPOINT` | Supabase S3-compatible storage settings |
| `SFP_DEMO_ENABLED` | If `true`, running the seeders will generate Demo users |
| `SFP_DEMO_*` | Credentials to inject into the Demo admin and staff accounts when seeding |
| `SFP_ADMIN_USERNAME`, `SFP_ADMIN_PASSWORD` | Required when seeding the initial Admin account |

## Railway CLI Commands

To manage this application via the [Railway CLI](https://docs.railway.com/cli-api/cli-reference):

**1. Deploying (from local):**
```bash
railway up --detach
```
*(Note: It is usually safer to `git push` to your linked branch to rely on a pure CI/CD flow, but `railway up` works perfectly for fast manual deployments).*

**2. Running Migrations:**
Migrations run automatically on deploy via the `releaseCommand` defined in `railway.json`. To run them manually on production:
```bash
railway run php artisan migrate --force
```

**3. Seeding the Database (Including Demo accounts):**
```bash
railway run php artisan db:seed --force
# Set SFP_ADMIN_USERNAME and SFP_ADMIN_PASSWORD for the initial Admin.
# Set SFP_DEMO_ENABLED=true and the SFP_DEMO_* credentials to create demo accounts.
railway run php artisan db:seed --class=GpsfpSeptember2026Seeder --force
```
The GPSFP data seeder is idempotent. Run it to load the supplied September 2026 cycle and school roster; it is not run automatically by `DatabaseSeeder` or the Railway release command.

**4. Viewing Logs:**
```bash
railway logs             # Stream latest live logs
railway logs --build     # View build logs
```

**5. Rolling Back:**
To rollback, you can view your deployments with `railway status` or via the Dashboard and either revert the git commit or redeploy a specific previous deployment ID.

## Troubleshooting

- **Mixed Content / Blank Styling**: If the page loads but has no styling, it means `APP_URL` isn't set to the exact `https://...` Railway URL, or the `TrustProxies` middleware isn't active.
- **Missing Assets**: If `public/build` assets are 404ing, ensure the `assets` stage of the Dockerfile properly ran `bun install` and `bun run build`.
- **Database Connection Refused**: Verify that `DB_URL` is using `mysql.railway.internal` and NOT the public TCP proxy (which may require manual unlocking/authentication).
- **Runtime startup or health-check failure**: Check the Railway deploy logs, the `PORT` value supplied by Railway, and the FrankenPHP/Caddy configuration in `Dockerfile` and `Caddyfile`.

## QA Validation (29-Sep-2026)
A complete End-to-End QA pass was performed against the live Railway environment utilizing Playwright automation. 
- **What was tested:** The entire authentication journey, Admin routes (Forms 4/7/10/12/13, Reports, Settings, Schools, Staff), Field Staff routes (Enter Delivery, Dashboard), Role-based access control, Mobile views, Dark mode, and PDF exports.
- **Pass/Fail:** 100% PASS with 0 HTTP 500 errors and 0 JavaScript runtime errors. S3 configuration (AWS_ENDPOINT) was correctly mapped with path-style requests, and PDF exports embedded Bangla (`solaimanlipi`) flawlessly without crashing.
- **What was fixed:** Shortened MySQL foreign key index names that exceeded 64 characters during deployments, and unified the Demo Seeder environment variable check to strictly use `config('sfp.demo.enabled')` instead of `env()`, guaranteeing proper seeding behind `config:cache`.
