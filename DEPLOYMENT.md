# Deployment — Hostinger Shared Hosting (cPanel/hPanel)

Complete step-by-step guide to deploy **Tekedar Hisaab / Ali Building Construction Group**
on Hostinger shared hosting. No Node runtime, no queues, no Redis required at runtime.

Stack: **Laravel + Blade + Tailwind + Alpine.js + MySQL**. Money stored as integer paisa.

---

## 0. Before you start (do this on your LOCAL machine)

Hostinger shared hosting has **no Node.js**, so frontend assets must be **built locally**
and uploaded.

```bash
# in the project folder, on your computer
composer install --optimize-autoloader --no-dev
npm install
npm run build          # generates public/build/ (compiled CSS/JS)
```

`public/build/` is gitignored, so after building either:
- upload `public/build/` manually along with the rest, **or**
- temporarily commit it for deployment (see step 5 note).

Make sure **`public/images/logo.jpeg`** exists (company logo used on invoices + sidebar).

---

## 1. Create the MySQL database (hPanel)

1. hPanel → **Databases → MySQL Databases**
2. Create a database, e.g. `uXXXX_tekedar`
3. Create a DB user + strong password, grant **ALL PRIVILEGES** on that database
4. Note down: **DB name**, **DB user**, **DB password**, **host** (usually `localhost`)

---

## 2. Upload the project files

**Option A — Git (if SSH available on your plan, Premium/Business):**
```bash
ssh uXXXX@your-server
cd ~
git clone https://github.com/talhakhan143/tekedar-hisaab.git tekedar
cd tekedar
composer install --optimize-autoloader --no-dev
```

**Option B — File Manager / FTP (any plan):**
- Zip the project locally **including** `vendor/` and `public/build/`
- Upload + extract into a folder **above** `public_html`, e.g. `~/tekedar`
- (Building `vendor/` locally avoids needing composer on the server)

---

## 3. Folder structure on cPanel — beginner walkthrough 📁

This is the part beginners get wrong. Read carefully.

A Laravel project has many folders, but **only the `public/` folder is allowed to be
public** (visitors must never reach `app/`, `.env`, etc.). On Hostinger the public web
root is the folder called **`public_html`**.

### What your project looks like locally (on your PC)

```
tekedar-hisaab/                 ← the whole Laravel project
├── app/                        ← code (KEEP PRIVATE)
├── bootstrap/
├── config/
├── database/
├── public/                     ← ONLY this is meant to be public
│   ├── index.php               ← the entry point
│   ├── .htaccess
│   ├── build/                  ← compiled CSS/JS (from `npm run build`)
│   └── images/logo.jpeg        ← company logo
├── resources/
├── routes/
├── storage/                    ← must be writable
├── vendor/                     ← installed libraries (KEEP PRIVATE)
├── .env                        ← secrets (KEEP PRIVATE)
└── artisan
```

### How it must sit on cPanel — pick ONE layout

---

#### ✅ Layout A — RECOMMENDED (app outside `public_html`)

App lives in a private folder; only `public/`'s **contents** go into `public_html`.

```
/home/uXXXX/                         ← your hosting account home (one level ABOVE public_html)
│
├── tekedar/                         ← put the WHOLE app here, EXCEPT the public folder
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/        ← chmod 775
│   ├── vendor/
│   ├── .env            ← your real credentials
│   └── artisan
│
└── public_html/                     ← the WEB ROOT (visitors land here)
    ├── index.php                    ← copied from tekedar/public/, then EDIT 2 lines (below)
    ├── .htaccess                    ← copied from tekedar/public/
    ├── build/                       ← copied from tekedar/public/build/
    ├── images/
    │   └── logo.jpeg                ← copied from tekedar/public/images/
    ├── favicon.ico
    └── robots.txt
```

**Steps (Hostinger File Manager):**
1. Upload/extract the project into `/home/uXXXX/tekedar` (everything).
2. Open `tekedar/public/` → **move ALL its contents** into `public_html/`.
3. Delete the now-empty `tekedar/public/` folder (optional).
4. Edit `public_html/index.php` — change two paths to point one level up + into `tekedar`:

```php
// BEFORE (default):
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// AFTER (app is in ../tekedar):
require __DIR__.'/../tekedar/vendor/autoload.php';
$app = require_once __DIR__.'/../tekedar/bootstrap/app.php';
```

That's it. `.env`, `app/`, `vendor/` stay private; visitors only ever see `public_html`.

---

#### ✅ Layout B — CLEANER (if your plan lets you set "Document Root")

Don't split anything. Put the whole app in `~/tekedar` and point the domain at its
`public` folder.

```
/home/uXXXX/
└── tekedar/                ← whole app, untouched
    ├── app/  bootstrap/  config/  database/  resources/  routes/
    ├── storage/  vendor/  .env  artisan
    └── public/             ← set the domain's Document Root to THIS folder
        ├── index.php
        ├── .htaccess
        ├── build/
        └── images/logo.jpeg
```

In hPanel → **Domains → (your domain) → Document Root** → set to
`tekedar/public`. No file moving, no `index.php` editing. Best for **subdomains** and
**addon domains**; the primary domain may be locked to `public_html` (then use Layout A).

---

#### 🟢 Layout C — EASIEST for beginners (whole app in `public_html` + a root `.htaccess`)

No file moving, no `index.php` editing. Upload the **entire app** into `public_html`,
then add ONE `.htaccess` at the `public_html` root that forwards every request into the
`public/` subfolder.

```
/home/uXXXX/
└── public_html/                ← upload the WHOLE Laravel app here
    ├── app/  bootstrap/  config/  database/  resources/  routes/
    ├── storage/  vendor/  .env  artisan
    ├── public/                 ← Laravel's real public folder
    │   ├── index.php
    │   ├── .htaccess           ← (already in the repo — keep it)
    │   ├── build/
    │   └── images/logo.jpeg
    └── .htaccess               ← CREATE THIS ONE (forwards to public/, content below)
```

Create `public_html/.htaccess` with exactly this:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Block direct access to sensitive files/folders
    RewriteRule ^(\.env|\.git|composer\.(json|lock)|artisan|storage|vendor|app|bootstrap|config|database|resources|routes|tests)(/|$) - [F,L]

    # Forward everything else into the public/ folder
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

- The first rule **blocks** `/.env`, `/app/...`, `/vendor/...` etc. (returns 403).
- The second rule sends all normal traffic into `public/`, where Laravel's own
  `public/.htaccess` takes over.
- Trade-off: app files physically sit inside `public_html`, but the rules above keep them
  unreachable. Layout A is slightly safer; Layout C is the simplest to set up.

---

> **Golden rule:** `index.php`, `build/`, `images/` must be reachable in the browser;
> `app/`, `.env`, `vendor/`, `storage/` must NOT. Test it: open
> `https://your-domain.com/.env` — it must show **403 Forbidden** or **404**, never the
> file contents. If you see the file, STOP and fix the layout.

### About the `.htaccess` files (so nothing breaks)

- **`public/.htaccess`** ships with the project — it does Laravel's pretty-URL routing,
  forces no trailing slashes, handles auth headers, and (defense-in-depth) denies dotfiles.
  Leave it as is. To force HTTPS, uncomment the two `RewriteCond/RewriteRule` HTTPS lines
  inside it **after** enabling the free SSL in hPanel (Hosting → SSL).
- **`public_html/.htaccess`** — only needed for **Layout C** (above). Layout A/B don't need it.
- If you get **500 Internal Server Error** right after deploy and the logs mention
  `.htaccess`, your host may not allow a directive — comment the `Options -Indexes` line
  or the `<FilesMatch>` block and retry. On Hostinger the shipped file works as-is.
- `mod_rewrite` is enabled on Hostinger by default; if routes 404, confirm the correct
  `.htaccess` is present in the web root.

---

## 4. Configure `.env`

Copy the example and edit:

```bash
cp .env.example .env
```

Set at minimum:

```env
APP_NAME="Ali Building Construction Group"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXX_tekedar
DB_USERNAME=uXXXX_user
DB_PASSWORD=your-db-password

SESSION_DRIVER=database      # or file
CACHE_STORE=database         # or file
QUEUE_CONNECTION=sync        # no worker on shared hosting
```

⚠️ `APP_DEBUG=false` in production — never expose stack traces.

---

## 5. Initialize the app (SSH) 

```bash
php artisan key:generate          # writes APP_KEY into .env
php artisan migrate --force       # creates all tables
php artisan db:seed --force       # creates the developer + owner users
php artisan storage:link          # public/storage symlink (uploaded logos)

# cache for speed (re-run after any code/config change)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **No SSH?** Run migrations another way:
> - Import the schema via **phpMyAdmin** (export it from a local `migrate` first), or
> - Temporarily add a protected route that calls `Artisan::call('migrate --force')`,
>   hit it once, then remove it.
> - `APP_KEY` can be generated locally and pasted into the server `.env`.

**Note on assets:** if you did NOT upload `public/build/`, the site will have no CSS.
Build locally (`npm run build`) and upload `public/build/`, or temporarily remove
`/public/build` from `.gitignore`, commit, push, pull on server, then revert.

---

## 6. File permissions

These two must be **writable** by the web server:

```bash
chmod -R 775 storage bootstrap/cache
```

(On Hostinger, File Manager → select folder → Permissions → 755/775.)

---

## 7. Login

After seeding, two accounts exist (change passwords immediately via the in-app menu):

| Role | Email | Password |
|------|-------|----------|
| Developer (hidden) | mr.talha143@gmail.com | `Spazio@786` |
| Owner | m_ali@aliconsgroup.pk | `m_ali_owner@786` |

- Owner: top-right avatar → **Profile & Password**
- Developer-only panel: top-right → **Developer · Users** (404 for everyone else)

Set company **address + phone** in **Settings** so they appear on invoices/vouchers.

---

## 8. Updating later (redeploy)

```bash
# local
npm run build
git add -A && git commit -m "..." && git push

# server (SSH)
git pull
composer install --optimize-autoloader --no-dev   # if deps changed
php artisan migrate --force                        # if new migrations
php artisan config:cache && php artisan route:cache && php artisan view:cache
# upload new public/build/ if assets changed
```

---

## 9. Troubleshooting

| Symptom | Fix |
|---------|-----|
| 500 error, blank page | `APP_DEBUG=true` temporarily; check `storage/logs/laravel.log`; fix permissions on `storage/` |
| No CSS / unstyled | `public/build/` missing — build locally + upload |
| "No application encryption key" | `php artisan key:generate` (or paste APP_KEY in `.env`) |
| Routes 404 | `.htaccess` missing in web root, or mod_rewrite off |
| DB connection refused | check `.env` DB creds + `DB_HOST=localhost` |
| Logo not on invoice | ensure `public/images/logo.jpeg` uploaded |
| Stale changes after deploy | re-run the `*:cache` artisan commands |

---

## 10. Hard reset (after handover / testing)

Settings → **Danger Zone → Hard Reset** (type `RESET`). Wipes ALL business data
(projects, vendors, workers, payments, materials, expenses, estimates) but **keeps the
two users and settings**. Irreversible — use only on a fresh handover.
