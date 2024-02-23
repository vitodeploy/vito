VOLUME ["/var/www/html", "/var/lib/mysql"]

FROM ubuntu:22.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive

RUN echo "mysql-server mysql-server/root_password password password" | debconf-set-selections
RUN echo "mysql-server mysql-server/root_password_again password password" | debconf-set-selections

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
    && apt install -y php8.1 php8.1-fpm php8.1-mbstring php8.1-mysql php8.1-mcrypt php8.1-gd php8.1-xml \
    php8.1-curl php8.1-gettext php8.1-zip php8.1-bcmath php8.1-soap php8.1-redis
COPY docker/standalone/php.ini /etc/php/8.1/cli/conf.d/99-vito.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# mysql
RUN wget -c https://dev.mysql.com/get/mysql-apt-config_0.8.22-1_all.deb \
    && mkdir -p /etc/apt/keyrings \
    && apt clean \
    && apt update \
    && dpkg -i mysql-apt-config_0.8.22-1_all.deb \
    && apt install mysql-server -y

RUN service mysql stop

# app
COPY . /var/www/html
RUN rm -rf /var/www/html/vendor
RUN rm -rf /var/www/html/.env
RUN composer install --no-dev --prefer-dist
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# webserver
RUN rm /etc/nginx/sites-available/default
RUN rm /etc/nginx/sites-enabled/default
COPY docker/standalone/nginx.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# supervisord
COPY docker/standalone/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# start
COPY docker/standalone/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80 3306

CMD ["/usr/bin/supervisord"]
