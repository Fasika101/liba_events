# Events & Ticket Sales

Laravel 12 + Breeze (Livewire) app for managing events, issuing tickets, and tracking revenue per sales agent. No payment gateway is wired; sales are recorded as confirmed.

## Features (MVP)
- Admin: create/edit events with price, schedule, capacity, and optional photo.
- Admin: create sales agents; view revenue per event and per agent.
- Agent: sell tickets (records buyer details, auto-generates code) and see their own sales.
- Stats: dashboard cards for total revenue, tickets, and events; per-agent and per-event tables.

## Stack
- PHP 8.2+, Laravel 12, Livewire Breeze, Tailwind/Vite.
- Database: MySQL/MariaDB (phpMyAdmin/cPanel friendly).
- Storage: local `storage/app/public` for event photos (link via `php artisan storage:link`).

## Setup
1) Requirements: PHP 8.2+, MySQL, Composer, Node 18+.  
2) Install deps: `composer install` and `npm install`.  
3) Copy env: `cp .env.example .env` and set DB_* for your cPanel MySQL.  
4) Generate key: `php artisan key:generate`.  
5) Migrate/seed: `php artisan migrate --seed`.  
6) Build assets: `npm run build` (or `npm run dev` locally).  
7) Storage symlink (for photos): `php artisan storage:link`.

### Default seeded accounts
- Admin: `admin@example.com / password`
- Agent: `agent@example.com / password`

## Running locally
```
php artisan serve
npm run dev   # optional HMR while building UI
```

## Deployment notes (cPanel)
- Upload the project, point the document root to `public/`.
- Set environment variables in cPanel (DB creds, APP_KEY, APP_URL).
- Run `composer install --no-dev`, `php artisan migrate --seed`, `php artisan storage:link`.
- Build assets locally or via `npm run build` if Node is available on the server.

## Progress log
- Scaffolded Laravel 12 with Breeze Livewire + Tailwind and built auth.
- Added roles (admin/agent) with middleware, dashboards, events CRUD, ticket sales, and agent creation.
- Added UI for admin/agent flows and seeded default accounts.
