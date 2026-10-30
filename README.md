# Safari Planning

Safari Planning is a Laravel operations workspace for coordinating safari bookings, guides, vehicles, transfers, resources, and team access.

## What it includes

- Booking and safari planning board with monthly workflows and reusable views
- Guide and vehicle records with planning permissions and assignment controls
- Transfer coordination for airport pickups and hotel-to-hotel movements
- Resource visibility for guides, vehicles, translators, and availability
- Role-based access for administrators, editors, commenters, and viewers
- Import/export templates, operational reports, activity logging, backups, and security controls

## Technology

- PHP 8.2+
- Laravel 12 and Laravel Jetstream
- Blade, JavaScript, Tailwind CSS, and Vite
- MySQL or another Laravel-supported relational database
- PHPUnit for automated tests

## Local setup

Requirements: PHP 8.2+ with the extensions required by Composer, Composer 2, a compatible Node.js release (Node 22.12+ is a suitable baseline for these Vite dependencies), npm, and a database.

```bash
git clone https://github.com/lifeoflen001/Safari-Planning.git
cd Safari-Planning
cp .env.example .env
composer install
php artisan key:generate
npm ci
```

Before migrating, configure `APP_URL` and the database in your local `.env`. For SQLite, create `database/database.sqlite` if it does not exist; for MySQL/PostgreSQL, create a dedicated local database and set its connection values. Use a separate test database.

```bash
php artisan migrate
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). The `cp` command above uses a Unix-style shell; in PowerShell use `Copy-Item .env.example .env`.

Review seeders before running them. Run `php artisan db:seed` only when you explicitly want their data in a disposable development database; do not assume they are safe for a populated installation.

## Development and checks

```bash
composer dev
php artisan test
npm run build
php artisan route:list
```

The login route is `/auth/login`; `/login` redirects there. Use an account provisioned through your reviewed development seeders or invitation workflow.

## Configuration

The environment example defaults to SQLite, database-backed sessions/cache/queues, and log-only mail. Configure SMTP to deliver invitations and password-reset messages. Use `composer dev` for the local server, queue listener, logs, and Vite, or run the required processes separately.

## Repository map

| Path | Purpose |
| --- | --- |
| `app/Http/Controllers/PlannerController.php` | Planning, guides, vehicles, and transfers |
| `app/Http/Controllers/SafariBoardController.php` | Monthly booking board and imports |
| `app/Http/Controllers/TaskController.php` | Tasks |
| `routes/web.php` | Authentication, permissions, modules, and operational routes |
| `resources` | Views and frontend source |
| `database` | Schema and development data |
| `tests` | Automated tests |

## Deployment

Serve the application from `public/` over HTTPS. Set production database/mail values outside Git, disable debug output, supervise required workers, and configure scheduled tasks after reviewing `routes/console.php`. Back up the database before migrations. Do not use destructive database reset commands on operational data.

## Project status

An actively developed operations platform. Confirm access permissions, import formats, mail delivery, and operational workflows in your own environment before rollout.
