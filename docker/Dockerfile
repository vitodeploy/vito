FROM dunglas/frankenphp:latest-php8.4

WORKDIR /var/www/html

# Install PHP extensions required by Vito
RUN install-php-extensions \
    intl \
    mbstring \
    xml \
    curl \
    zip \
    bcmath \
    gd \
    redis \
    pdo_sqlite \
    pcntl \
    opcache

# Install additional tools
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    supervisor \
    redis-server \
    openssh-client \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Copy application
COPY . /var/www/html
RUN rm -rf /var/www/html/.git /var/www/html/vendor /var/www/html/node_modules

# Install composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# PHP configuration for Octane
COPY docker/php.ini /usr/local/etc/php/conf.d/99-vito.ini

# Cron for scheduler
RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

# Supervisor for Horizon
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80 443

CMD ["/start.sh"]
