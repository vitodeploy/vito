FROM ubuntu:22.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive

RUN echo "mysql-server mysql-server/root_password password password" | debconf-set-selections
RUN echo "mysql-server mysql-server/root_password_again password password" | debconf-set-selections

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2 dnsutils librsvg2-bin fswatch \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /usr/share/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/usr/share/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.1-cli php8.1-dev \
       php8.1-pgsql php8.1-sqlite3 php8.1-gd php8.1-imagick \
       php8.1-curl \
       php8.1-imap php8.1-mysql php8.1-mbstring php8.1-ssh2\
       php8.1-xml php8.1-zip php8.1-bcmath php8.1-soap \
       php8.1-intl php8.1-readline \
       php8.1-ldap \
       php8.1-msgpack php8.1-igbinary php8.1-redis php8.1-swoole \
       php8.1-memcached php8.1-pcov php8.1-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get update \
    && apt-get install -y mysql-server mysql-client \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY docker/prod/init.sql /docker-entrypoint-initdb.d/
RUN echo "source /docker-entrypoint-initdb.d/init.sql" > /docker-entrypoint-initdb.d/00-init.sql
COPY docker/prod/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/prod/php.ini /etc/php/8.1/cli/conf.d/99-vito.ini
COPY . /var/www/html
RUN composer install --no-dev --prefer-dist

EXPOSE 8000

CMD ["/usr/bin/supervisord"]
