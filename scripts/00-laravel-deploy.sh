#!/usr/bin/env bash
set -e

echo "=== Running Laravel Deploy Script ==="

# Ensure storage directories exist and are writable
echo "Setting up storage directories..."
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache configuration, routes, and views for production performance
echo "Caching Laravel configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations only when RUN_MIGRATIONS environment variable is set to true
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
else
    echo "Skipping database migrations (set RUN_MIGRATIONS=true to run them)."
fi

# Run database seeders if RUN_SEEDERS environment variable is set to true
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Running production seeder (runs once; skipped if data already exists)..."
    php artisan db:seed --class=ProductionSeeder --force
fi

# Reset every existing user's password to the development password when RESET_USER_PASSWORDS is true
if [ "$RESET_USER_PASSWORDS" = "true" ]; then
    echo "Resetting user passwords..."
    php artisan db:seed --class=ResetUserPasswordsSeeder --force
fi

# Add users from CmsUserSeeder and UserSeeder that do not exist yet when RUN_USER_SYNC is true
if [ "$RUN_USER_SYNC" = "true" ]; then
    echo "Syncing missing users..."
    php artisan db:seed --class=SyncUsersSeeder --force
fi

# Re-seed the accounts and their contact information when RUN_ACCOUNT_SYNC is true
if [ "$RUN_ACCOUNT_SYNC" = "true" ]; then
    echo "Syncing accounts and contact information..."
    php artisan db:seed --class=SyncAccountsSeeder --force
fi

if [ "$SYNC_NEW_ACCOUNTS" = "true" ]; then
    echo "Syncing accounts and contact information..."
    php artisan migrate:fresh --seed
fi
echo "=== Laravel Deploy Script Completed Successfully ==="
