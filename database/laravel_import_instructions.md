# Import the existing STPM database

1. Start MySQL in Laragon.
2. Import `database/sistem_peperiksaan_stpm.sql` into the `sistem_peperiksaan_stpm` database using HeidiSQL/phpMyAdmin or `mysql -u root < database/sistem_peperiksaan_stpm.sql`.
3. Configure the MySQL values in `.env` if your local credentials differ.
4. Run `php artisan migrate` to add Laravel's authentication `role` field to the existing `users` table.
5. Set each existing user's `password` to a Laravel bcrypt hash before using the Laravel login. Existing plain-text passwords from the legacy app are not accepted by Laravel authentication.

The legacy PHP screens have been preserved under `legacy/` as a temporary reference during the remaining module conversion.
