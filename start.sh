#!/bin/bash
set -e

php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
php artisan tinker --execute="App\Models\User::where('email','admin@admin.com')->first()?->forceFill(['password' => bcrypt('adminpassword'), 'is_admin' => true])->save();"
php artisan storage:link --force

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
