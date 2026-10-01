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

# Run database migrations automatically
echo "Running database migrations..."
php artisan migrate --force

echo "=== Laravel Deploy Script Completed Successfully ==="
