# Features & Architecture Notes

Quick map of the custom features built on top of the base Laravel app, with file
pointers — so future work is easy to pick up.

## Auth & users

Only **two accounts** (seeded in `database/seeders/DatabaseSeeder.php`):
- **Developer** (hidden) — `is_developer = true`. Can change any user's username/email/password
  with no confirmation. Panel at `/developer/users`, gated by `EnsureDeveloper` middleware
  (404 for everyone else).
- **Owner** — standard user. Changes own password (needs current password, no email verify).

| Concern | File |
|---|---|
| Developer flag | migration `..._add_is_developer_to_users.php`, `app/Models/User.php` |
| Developer panel | `app/Http/Controllers/DeveloperUserController.php`, `resources/views/developer/users.blade.php` |
| Middleware | `app/Http/Middleware/EnsureDeveloper.php` (alias `developer` in `bootstrap/app.php`) |
| Owner password | `app/Http/Controllers/Auth/PasswordController.php` |
| User menu (Profile/Developer/Logout) | `resources/views/layouts/app.blade.php` (topbar dropdown) |

Delete-account is removed (route + UI gone). Only password + username change remain.

## Branding / logo

- Logo file: `public/images/logo.jpeg` (committed).
- Sidebar header + invoices use it. Company name hardcoded **"Ali Building Construction Group"**
  on vouchers; sidebar uses Settings `company_logo` if set, else this file.

## Printable vouchers / invoices

Every money transaction has a printable document. Browser-print, **A4 (default) + POS 80mm**
toggle on each page (`?size=a4|pos`). Footer credit on every page.

| Voucher | Route | View |
|---|---|---|
| Purchase invoice (vendor) | `vouchers.material` | `vouchers/material.blade.php` |
| Wage voucher (labour) | `vouchers.wage` | `vouchers/wage.blade.php` |
| Advance/recovery | `vouchers.advance` | `vouchers/advance.blade.php` |
| Client payment receipt | `vouchers.client-payment` | `vouchers/client.blade.php` |
| Expense voucher | `vouchers.expense` | `vouchers/expense.blade.php` |
| Worker account statement | `vouchers.worker-statement` | `vouchers/worker-statement.blade.php` |

- Controller: `app/Http/Controllers/VoucherController.php`
- Shared print layout (A4/POS CSS, `@page` sizing, letterhead, footer): `resources/views/vouchers/layout.blade.php`
- Amount-in-words helper: `app/Support/NumberToWords.php` (Lakh/Crore system)
- Print 🖨 buttons live in every transaction table (project tabs, materials/money-in/money-out lists, worker page) — all `target="_blank"`.
- After recording any payment, a toast shows a **🖨 Print Voucher** link (flashed via `voucher_url` / `voucher_label` session keys, rendered in `layouts/app.blade.php`).

## Dashboard

- **"This Month — Cash In/Out"** card = `monthReceived − monthSpent` (cash basis, NOT profit).
  Renamed from "Net Profit" to avoid confusion. Logic in `app/Services/DashboardData.php`.
- **Real profit** is project-based (accrued + cash) — "Overall Profit" card + per-project closeout.
- Cash basis: material **paid** counts (unpaid udhaar does not); client **net received** counts.

## Worker management

- Workers are identified by **auto-increment ID** shown everywhere (`#1`, `#2`...) so same-named
  workers are distinguishable. Never merge by name. Dropdowns/tables show `#id · role`.

## Settings

`resources/views/settings/edit.blade.php` + `SettingController`:
- Company name, **address, phone** (address/phone print on vouchers), logo upload
- Default retention/wastage %, overhead allocation toggle, worker role list
- **Danger Zone → Hard Reset** (`ResetController@reset`, route `hard-reset`): wipes all business
  tables, keeps users + settings. Requires typing `RESET`.

## Money model

All amounts are **integer paisa** (`App\Support\Money`, `App\Casts\PaisaCast`). Never store
rupees as float. `Money::format/short`, `Money::toPaisa/toRupees`.

## Dev notes

- Frontend: Vite + Tailwind + Alpine. `npm run build` before deploy (no Node on shared host).
- Knowledge graph: `code-review-graph` MCP (see `CLAUDE.md`) — use it before grepping.
- Deploy: see `DEPLOYMENT.md` (Hostinger shared hosting).
