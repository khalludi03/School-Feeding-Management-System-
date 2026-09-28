# Railway Deployment Documentation

## Architecture
The application runs on Railway using a multi-stage Dockerfile deployment to a single `sfp-web-app` service.
- **Frontend Build**: Built with Vite & `oven/bun:1` (outputs to `public/build`).
- **Backend Runtime**: `php:8.4-apache` running `mpm_prefork` to correctly handle single-threaded requests (like mPDF generation) concurrently by spinning up child worker processes.
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
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | Must all be `database` so state isn't lost on deploy |
| `SESSION_SECURE_COOKIE` | Enforces HTTPS-only cookies (`true` in production) |
| `LOG_CHANNEL` | Must be `stderr` to pipe logs to Railway's dashboard |
| `AWS_*` | Assorted variables for Supabase S3 storage (key, secret, region, bucket, endpoint, use_path_style) |
| `SEED_DEMO_ACCOUNTS` | If `true`, running the seeders will generate Demo users |
| `SFP_DEMO_*` | Credentials to inject into the Demo admin and staff accounts when seeding |

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
# (The SEED_DEMO_ACCOUNTS=true variable ensures demo accounts are created during the seed).
```

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
- **Infinite Restart / Apache Crash**: If `AH00534: apache2: Configuration error: More than one MPM loaded.` appears in the logs, it means the Debian base image is conflicting with itself. Ensure the Dockerfile explicitly runs `rm -f /etc/apache2/mods-enabled/mpm_event.* && a2enmod mpm_prefork`.
