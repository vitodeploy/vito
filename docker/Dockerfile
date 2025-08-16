FROM ubuntu:24.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive

# upgrade
RUN apt-get update && apt-get upgrade -y && apt-get autoremove -y

# requirements
RUN apt-get install -y software-properties-common curl zip unzip gcc nginx redis-server git \
  cron gnupg gosu curl ca-certificates zip unzip supervisor libcap2-bin libpng-dev \
  dnsutils librsvg2-bin fswatch wget openssh-client \
  && add-apt-repository ppa:ondrej/php -y \
  && apt-get update \
  && apt-get install -y php8.4 php8.4-fpm php8.4-mbstring php8.4-mcrypt php8.4-gd php8.4-xml \
  php8.4-curl php8.4-gettext php8.4-zip php8.4-bcmath php8.4-soap php8.4-redis php8.4-sqlite3 php8.4-intl

# php
COPY docker/php.ini /etc/php/8.4/cli/conf.d/99-vito.ini
COPY docker/php.ini /etc/php/8.4/fpm/conf.d/99-vito.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# app
RUN rm -rf /var/www/html
COPY . /var/www/html
RUN rm -rf /var/www/html/.git /var/www/html/vendor /var/www/html/node_modules
RUN composer install --no-dev
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
