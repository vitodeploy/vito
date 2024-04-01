FROM ubuntu:22.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive

# upgrade
RUN apt clean && apt update && apt update && apt upgrade -y && apt autoremove -y

# requirements
RUN apt install -y software-properties-common curl zip unzip git gcc

# nginx
RUN apt install -y nginx

# php
RUN apt update \
    && apt install -y gnupg gosu curl ca-certificates zip unzip git supervisor libcap2-bin libpng-dev \
    python2 dnsutils librsvg2-bin fswatch wget \
    && add-apt-repository ppa:ondrej/php -y \
    && apt update \
    && apt install -y php8.2 php8.2-fpm php8.2-mbstring php8.2-mcrypt php8.2-gd php8.2-xml \
    php8.2-curl php8.2-gettext php8.2-zip php8.2-bcmath php8.2-soap php8.2-redis php8.2-sqlite3
COPY docker/php.ini /etc/php/8.2/cli/conf.d/99-vito.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# app
RUN rm -rf /var/www/html
RUN git clone -b 1.x https://github.com/vitodeploy/vito.git /var/www/html
RUN git checkout $(git tag -l --merged 1.x --sort=-v:refname | head -n 1)
RUN composer install --no-dev --prefer-dist
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# webserver
RUN rm /etc/nginx/sites-available/default
RUN rm /etc/nginx/sites-enabled/default
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# start
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
