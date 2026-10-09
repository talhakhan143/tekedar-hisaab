# Tekedar Hisaab — Money & Profit Tracking

Single-admin money-management and profit-tracking app for a Pakistani building/interior
contractor (tekedar). Tracks every rupee in/out per theka (contract) and answers, at any
moment: per-project profit/loss and overall monthly position.

Built with **Laravel 13 + Blade + Tailwind + Alpine.js + MySQL**. Runs on Hostinger shared
cPanel hosting (no Node runtime, no queues, no Redis). All money stored as **integer paisa
(BIGINT)** — zero floating-point errors.

## Core concepts

- **Accrued profit** = total gross client billing − total cost incurred.
- **Cash profit** = (net received + retention released) − cash actually paid out. Excludes
  retention still held by the client. These two are tracked **separately** everywhere.
- **Retention** accrues as a separate receivable; only a `retention_release` moves it to cash.
- **Estimate vs Actual** per category is the heart of the app — that gap is the profit.

## Local development

```bash
composer install
cp .env.example .env        # then set DB_* + APP_TIMEZONE=Asia/Karachi
php artisan key:generate
php artisan migrate --seed   # creates admin + full demo project
npm install && npm run build
php artisan storage:link
php artisan serve
```

Login: **mali@baryal.com.pk / Ali@786**

The demo seeder creates one realistic project: 1500 sq.ft full-finished theka @ ₨2,200/sqft,
7% retention, 3 client payments, 10 material purchases (2 on udhaar), 4 workers with
attendance + 2 advances, 6 other expenses, monthly overheads.

## Deploy to Hostinger (cPanel)

1. Build assets locally and commit `public/build` (no Node on the server):
   ```bash
   npm run build
   ```
2. Upload the project (git or File Manager) **outside** `public_html`, e.g. `~/tekedar`.
3. Point the domain/subdomain document root to `~/tekedar/public`, OR move `public/*` into
   `public_html` and adjust `index.php` paths accordingly.
4. Create a MySQL DB + user in cPanel; put credentials in `.env`
   (`APP_ENV=production`, `APP_DEBUG=false`, `APP_TIMEZONE=Asia/Karachi`).
5. SSH (or cPanel Terminal):
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force --seed     # drop --seed if you don't want demo data
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
6. **Cron** (cPanel → Cron Jobs, every minute):
   ```
   * * * * * php /home/USER/tekedar/artisan schedule:run >> /dev/null 2>&1
   ```
   (No scheduled jobs ship yet; the cron is ready for future reminders/aging tasks.)

## Tech notes

- Money helper: `app/Support/Money.php` (`toPaisa`, `format`, `short`) + `@money`/`@moneyc`
  Blade directives + `App\Casts\PaisaCast`.
- P&L engine: `app/Services/ProjectFinance.php` (per project) and
  `app/Services/DashboardData.php` (all-company).
- Everything financial is soft-deleted (audit trail); purchase reassignments logged to `audit_logs`.
- Reports export to CSV (streamed) and PDF (`barryvdh/laravel-dompdf`).

## Modules

Dashboard · Projects (CRUD + P&L) · Estimates · Money In (client payments + retention) ·
Money Out (materials + wages + expenses + overheads) · Workers (attendance/advances/wages) ·
Vendors (ledger + payables) · Reports (monthly/outstanding/closeout, CSV+PDF) · Settings.
