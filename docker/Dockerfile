FROM ubuntu:24.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get install -y software-properties-common

# Add repositories
RUN add-apt-repository ppa:ondrej/php -y

# upgrade
RUN apt-get update && apt-get upgrade -y && apt-get autoremove -y

# requirements
RUN apt-get install -y curl zip unzip gcc nginx \
    cron gnupg gosu curl ca-certificates zip unzip supervisor libcap2-bin libpng-dev \
    dnsutils librsvg2-bin fswatch wget openssh-client \
    php8.2 php8.2-fpm php8.2-mbstring php8.2-mcrypt php8.2-gd php8.2-xml \
    php8.2-curl php8.2-gettext php8.2-zip php8.2-bcmath php8.2-soap php8.2-redis php8.2-sqlite3 php8.2-intl

# php
COPY docker/php.ini /etc/php/8.2/cli/conf.d/99-vito.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# app
RUN rm -rf /var/www/html
COPY . /var/www/html
RUN rm -rf /var/www/html/.git /var/www/html/vendor /var/www/html/node_modules
RUN composer install --no-dev --prefer-dist
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# webserver
RUN rm /etc/nginx/sites-available/default
RUN rm /etc/nginx/sites-enabled/default
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

# supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# start
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
