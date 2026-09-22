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
- Blade, React, JavaScript, Tailwind CSS, and Vite
- MySQL or another Laravel-supported relational database
- PHPUnit for automated tests

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Set database, mail, and application settings in your local `.env`. Never commit `.env`, production credentials, or operational data.

## Useful commands

```bash
php artisan test
npm run build
php artisan route:list
```

## Project status

An actively developed operations platform. The public repository contains application code and sample configuration only; production data and secrets are managed outside Git.
