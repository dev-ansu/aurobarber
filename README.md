# AuroBarber

AuroBarber is a SaaS product for barbershop scheduling and operations, developed by Aurosoft.

## Requirements

- PHP 8.3 or later
- Composer
- PostgreSQL
- Node.js and npm for frontend assets

## Local setup

1. Install PHP dependencies with `composer install`.
2. Copy `.env.example` to `.env` and configure the local database and application key.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate`.
5. Install frontend dependencies with `npm install` and build assets with `npm run build`.
6. Start the application with `php artisan serve`.

Google Drive backups are optional and require the credentials in `.env.example`. The SaaS master seeder requires explicit `SAAS_MASTER_*` values and does not provide default credentials.

## Validation

- Run tests with `php artisan test`.
- Format PHP with `vendor/bin/pint`.

The default test suite contains AuroBarber tests only. Review database migrations and make a backup before applying them to a shared or production environment.
