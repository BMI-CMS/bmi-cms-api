FROM richarvey/nginx-php-fpm:3.1.6

# Install PostgreSQL client libraries and PHP pdo_pgsql extension
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql

# Environment variables for the base image
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel default production settings
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

# Copy application files
COPY . /var/www/html

# Normalize line endings and set permissions on scripts
RUN sed -i 's/\r$//' /var/www/html/scripts/00-laravel-deploy.sh \
    && chmod +x /var/www/html/scripts/00-laravel-deploy.sh

# Install production composer packages
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php artisan package:discover --ansi

# Ensure storage directories exist and grant permissions
RUN mkdir -p /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/logs \
    && chown -R nginx:nginx /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]
