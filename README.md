# e-Exam STPM (Laravel 13)

The project now runs on Laravel 13. The old standalone PHP pages are preserved in `legacy/` during the conversion. New requests should be implemented as Laravel routes, controllers, Blade views, middleware, and Eloquent/query-builder operations.

## Requirements

- PHP 8.3 or newer with PDO MySQL
- Composer 2
- MySQL or MariaDB

## Setup

1. Start MySQL in Laragon and import `database/sistem_peperiksaan_stpm.sql`.
2. Copy `.env.example` to `.env` if needed and set database credentials.
3. Run `composer install` and `php artisan key:generate`.
4. Run `php artisan migrate` to add the Laravel `role` column to the existing `users` table.
5. Start the app with `php artisan serve` and open `/login`.

The existing legacy app may store plaintext passwords. On successful first Laravel login, the matching password is upgraded to a bcrypt hash. Change all default/shared passwords after migration.

## Conversion status

- Laravel 13 skeleton, configuration, database connection, and routes: done.
- Laravel session authentication, login throttling, legacy ID/email lookup, lazy password hash upgrade, logout: done.
- Role-aware dashboard shell: done.
- Admin, super admin, teacher-subject, teacher-class, and student module screens/actions: pending conversion from `legacy/`.
- Legacy SQL dump remains the schema/data import source; schema is not yet fully represented as Laravel migrations/models.
- Screens for recovery, account settings, management CRUD, class-subject assignment, exam scheduling, marks, result views, announcements, and reports remain to be ported.

The framework does not serve PHP files from `legacy/`; the old pages are reference material only.
