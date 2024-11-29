#!/usr/bin/env bash

{ # This ensures the entire script is downloaded #

    # VitoDeploy header banner
    function vitodeploy_header() {
        clear

        ASCII_LOGO="
#   __      ___ _        _____             _              
#   \ \    / (_) |      |  __ \           | |             
#    \ \  / / _| |_ ___ | |  | | ___ _ __ | | ___  _   _  
#     \ \/ / | | __/ _ \| |  | |/ _ \ '_ \| |/ _ \| | | | 
#      \  /  | | || (_) | |__| |  __/ |_) | | (_) | |_| | 
#    ___\/   |_|\__\___/|_____/ \___| .__/|_|\___/ \__, | 
#   |_   _|         | |      | | |  | |      |__ \  __/ | 
#     | |  _ __  ___| |_ __ _| | | _|_|_ __     ) ||___/__
#     | | | '_ \/ __| __/ _' | | |/ _ \ '__|   / /  \ \/ /
#    _| |_| | | \__ \ || (_| | | |  __/ |     / /_ _ >  < 
#   |_____|_| |_|___/\__\__,_|_|_|\___|_|    |____(_)_/\_\ 
#                                                         
#############################################################
"

        # Define the color gradient (shades of blue and cyan)
        COLORS=(
            '\033[38;5;45m' # Royal Blue
            '\033[38;5;51m' # Cornflower Blue
            '\033[38;5;57m' # Deep Sky Blue
            '\033[38;5;63m' # Dodger Blue
            '\033[38;5;69m' # Sky Blue
            '\033[38;5;75m' # Light Blue
            '\033[38;5;81m' # Cyan
        )

        # Split the ASCII art into lines
        _IFS=${IFS}
        IFS=$'\n' read -rd '' -a LINES <<< "${ASCII_LOGO}"

        # Print each line with the corresponding color
        for i in "${!LINES[@]}"; do
            COLOR_INDEX=$((i % ${#COLORS[@]}))
            echo -e "${COLORS[COLOR_INDEX]}${LINES[i]}"
        done

        # End color
        echo -e -n "\e[0m\n"

        # Restore default IFS
        IFS=${_IFS}
    }

    # Handle user input
    function vitodeploy_input() {
        # Detect server IP address
        export V_SERVER_IP_PRIVATE && V_SERVER_IP_PRIVATE=${V_SERVER_IP_PRIVATE:-$(get_ip_private)}
        export V_SERVER_IP_PUBLIC && V_SERVER_IP_PUBLIC=${V_SERVER_IP_PUBLIC:-$(get_ip_public)}

        export V_USERNAME
        while [[ -z "${V_USERNAME}" ]]; do
            read -rp "System account username [vito]: " -e V_USERNAME
        done

        if [[ -z "${V_PASSWORD}" ]]; then
            export V_PASSWORD && V_PASSWORD=$(openssl rand -base64 12)
        fi

        export V_ADMIN_EMAIL
        while [[ -z "${V_ADMIN_EMAIL}" || $(validate_email_address "${V_ADMIN_EMAIL}") == false ]]; do
            read -rp "Vito dashboard admin email: " -e V_ADMIN_EMAIL
        done

        export V_ADMIN_PASSWORD
        while [[ -z "${V_ADMIN_PASSWORD}" ]]; do
            read -rp "Vito dashboard admin password: " -e V_ADMIN_PASSWORD
        done

        export V_APP_ENV
        while [[ "${V_APP_ENV}" != prod* && "${V_APP_ENV}" != local* ]]; do
            read -rp "Vito environment [production/local]: " -i production -e V_APP_ENV
        done

        export V_USE_CUSTOM_DOMAIN
        export V_CUSTOM_DOMAIN
        export V_APP_URL

        if [[ "${V_APP_ENV}" == prod* ]]; then
            while [[ "${V_USE_CUSTOM_DOMAIN}" != y* && "${V_USE_CUSTOM_DOMAIN}" != Y* &&
                "${V_USE_CUSTOM_DOMAIN}" != n* && "${V_USE_CUSTOM_DOMAIN}" != N* ]]; do
                read -rp "Do you wish to setup Vito with a custom domain? [y/n]: " -e V_USE_CUSTOM_DOMAIN
            done

            if [[ "${V_USE_CUSTOM_DOMAIN}" == y* || "${V_USE_CUSTOM_DOMAIN}" == Y* ]]; then
                while [[ -z "${V_CUSTOM_DOMAIN}" || $(validate_domain_name "${V_CUSTOM_DOMAIN}") == false ]]; do
                    read -rp "Your valid domain name [mydomain.com]: " -e V_CUSTOM_DOMAIN
                done

                V_APP_URL="http://${V_CUSTOM_DOMAIN}"
            else
                V_CUSTOM_DOMAIN="${V_SERVER_IP_PUBLIC}"
                V_APP_URL="http://${V_CUSTOM_DOMAIN}"
            fi

            if [[ $(validate_domain_name "${V_CUSTOM_DOMAIN}") == true ]]; then
                if [[ $(host -4 "${V_CUSTOM_DOMAIN}" | awk 'NR==1 {print $NF}') != "${V_SERVER_IP_PUBLIC}" &&
                $(host "${V_CUSTOM_DOMAIN}" | awk 'NR==2 {print $NF}') != "${V_SERVER_IPV6_PUBLIC}" ]]; then
                    if [[ ${V_INPUT_RETRY} -lt 1 ]]; then
                        echo ""
                        echo "Your domain '${V_CUSTOM_DOMAIN}' is not pointing to this server."
                        echo "To implement a custom domain in a production environment, "
                        echo "You'll need to create an A record in your DNS settings. "
                        echo "This record should be pointed to the following IP addresses"
                        echo "IPv4 Address (A): ${V_SERVER_IP_PUBLIC}"
                        echo ""
                    else
                        echo "Retry checking DNS record for ${V_CUSTOM_DOMAIN}..."
                    fi

                    if [[ "${NONINTERACTIVE}" != y* && "${NONINTERACTIVE}" != Y* ]]; then
                        read -t 600 -rp "Press [Enter] to retry or [Ctrl+C] to cancel..." < /dev/tty
                    else
                        sleep 3 &
                        wait # Wait for termination signal (ctrl+z / ctrl+c)
                    fi

                    # Retry checking DNS record (max. 10x retries)
                    if [[ ${V_INPUT_RETRY} -lt 10 ]]; then
                        return 1
                    else
                        echo -e "\nUnfortunately, we were unable to successfully install VitoDeploy \non your server using custom domain '${V_CUSTOM_DOMAIN}'."
                        exit 1
                    fi
                fi
            fi
        else
            V_CUSTOM_DOMAIN="localhost"
        fi

        return 0
    }

    # Make sure only root or sudo user can run this script
    function check_root_access() {
        if [[ "$(id -u)" -ne 0 ]]; then
            echo "This installer script must be run as root or with a sudo user."
            if [[ $(groups "$(id -un)" | grep -c sudo) -ne 0 ]]; then
                echo -e "\nFor a sudo user, you can run the following command:"
                echo "curl -sLO https://raw.githubusercontent.com/vitodeploy/vito/${VITO_VERSION}/scripts/install.sh && sudo bash ./install.sh"
            fi
            exit 1
        fi
    }

    # Check for supported OS
    function check_supported_os() {
        OS_DISTRIB_NAME=${OS_DISTRIB_NAME:-$(lsb_release -is)}
        OS_RELEASE_NAME=${OS_RELEASE_NAME:-$(lsb_release -cs)}

        case "${OS_DISTRIB_NAME}" in
            "Ubuntu" | "ubuntu")
                DISTRIB_NAME="ubuntu"
                case "${OS_RELEASE_NAME}" in
                    "noble" | "jammy")
                        RELEASE_NAME="${OS_RELEASE_NAME}"
                        ;;
                    *)
                        RELEASE_NAME="unsupported"
                        ;;
                esac
                ;;
            *)
                DISTRIB_NAME="unsupported"
                ;;
        esac

        if [[ "${DISTRIB_NAME}" == "unsupported" || "${RELEASE_NAME}" == "unsupported" ]]; then
            echo "Sorry, this Linux distribution isn't supported."
            echo "Currently, VitoDeploy only support Ubuntu Linux."
            exit 1
        fi
    }

    # Get server private IP Address
    function get_ip_private() {
        local SERVER_IP_PRIVATE \
            && SERVER_IP_PRIVATE=$(ip addr | grep 'inet' | grep -v inet6 \
                | grep -vE '127\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}' \
                | grep -oE '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}' | head -1)

        echo "${SERVER_IP_PRIVATE}"
    }

    # Get server public IP Address
    function get_ip_public() {
        local SERVER_IP_PRIVATE && SERVER_IP_PRIVATE=$(get_ip_private)
        local SERVER_IP_PUBLIC \
            && SERVER_IP_PUBLIC=$(curl -sk --ipv4 --connect-timeout 10 --retry 1 --retry-delay 0 https://freeipapi.com/)

        # Hack to detect public IP address behind NAT
        if [[ "${SERVER_IP_PRIVATE}" == "${SERVER_IP_PUBLIC}" ]]; then
            echo "${SERVER_IP_PRIVATE}"
        else
            echo "${SERVER_IP_PUBLIC}"
        fi
    }

    # Validate domain name format
    function validate_domain_name() {
        local DOMAIN_NAME=${1}

        if grep -qP "(?=^.{4,253}\.?$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}\.?$)" <<< "${DOMAIN_NAME}"; then
            echo true
        else
            echo false
        fi
    }

    # Validate email address format
    function validate_email_address() {
        local EMAIL_ADDRESS=${1}
        local EMAIL_REGEX="^(([A-Za-z0-9]+((\.|\-|\_|\+)?[A-Za-z0-9]?)*[A-Za-z0-9]+)|[A-Za-z0-9]+)@(([A-Za-z0-9]+)+((\.|\-|\_)?([A-Za-z0-9]+)+)*)+\.([A-Za-z]{2,})+$"

        if grep -qP "${EMAIL_REGEX}" <<< "${EMAIL_ADDRESS}"; then
            echo true
        else
            echo false
        fi
    }

    # Upgrading OS & install prerequisites
    function install_prerequisites() {
        echo -e "\nUpgrading OS and install prerequisites"

        # Upgrade OS
        echo "Updating operating system"
        apt remove needrestart -y
        apt clean \
            && apt update -qq -y --fix-missing \
            && apt upgrade -qq -y \
            && apt autoremove -q -y

        # Install requirements
        echo "Installing required dependencies"
        apt install -qq -y apt-transport-https apt-utils build-essential curl dnsutils git gcc net-tools software-properties-common sqlite3 unzip zip
    }

    # Vitodeploy installation
    function vitodeploy_install() {
        echo -e "\nInstalling VitoDeploy, please sit tight..."

        # Create system user account
        echo -e "\nCreating system user account for '${V_USERNAME}'"
        if [[ -z $(getent passwd "${V_USERNAME}") ]]; then
            HASHED_PASSWORD=$(openssl passwd -1 "${V_PASSWORD}")
            useradd -p "${HASHED_PASSWORD}" "${V_USERNAME}" \
                && usermod -aG sudo "${V_USERNAME}"
            touch /etc/sudoers.d/90-vito-users \
                && echo "${V_USERNAME} ALL=(ALL) NOPASSWD:ALL" | tee -a /etc/sudoers.d/90-vito-users
            mkdir "/home/${V_USERNAME}"
            mkdir "/home/${V_USERNAME}/.ssh"
            chown -R "${V_USERNAME}:${V_USERNAME}" "/home/${V_USERNAME}"
            chsh -s /bin/bash "${V_USERNAME}"
            su - "${V_USERNAME}" -c "ssh-keygen -t rsa -N '' -C '${V_ADMIN_EMAIL}' -f ~/.ssh/id_rsa" <<< y
            echo "User '${V_USERNAME}' created and added to sudo"
        else
            echo "System user account '${V_USERNAME}' already exists"
            exit 1
        fi

        install_prerequisites

        # Python (required to install Certbot)
        echo "Installing Python (required for Certbot)"
        add-apt-repository ppa:deadsnakes/ppa -y \
            && apt update -qq -y \
            && apt install -qq -y python3.11 python3.11-dev python3.11-venv \
            && update-alternatives --install /usr/bin/python python "$(command -v python3.11)" 311 \
            && update-alternatives --set python /usr/bin/python3.11

        # Certbot
        echo "Installing Certbot Let's Encrypt client"
        python -m venv /opt/certbot/ \
            && /opt/certbot/bin/pip install --upgrade pip setuptools cffi \
            && /opt/certbot/bin/pip install --upgrade certbot certbot-nginx \
            && ln -sf /opt/certbot/bin/certbot /usr/bin/certbot
        if [[ -d /etc/letsencrypt/accounts/acme-v02.api.letsencrypt.org/directory ]]; then
            certbot update_account --email "${V_ADMIN_EMAIL}" --no-eff-email --agree-tos
        else
            certbot register --email "${V_ADMIN_EMAIL}" --no-eff-email --agree-tos
        fi

        # Nginx
        echo "Installing Nginx webserver"
        add-apt-repository ppa:ondrej/nginx -y \
            && apt update -qq -y \
            && apt install -qq -y nginx libnginx-mod-brotli libnginx-mod-http-cache-purge
        export V_NGINX_CONFIG="user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;
events {
  worker_connections 768;
}
http {
  sendfile on;
  tcp_nopush on;
  tcp_nodelay on;
  keepalive_timeout 65;
  types_hash_max_size 2048;
  include /etc/nginx/mime.types;
  default_type application/octet-stream;
  ssl_protocols TLSv1 TLSv1.1 TLSv1.2; # Dropping SSLv3, ref: POODLE
  ssl_prefer_server_ciphers on;
  access_log /var/log/nginx/access.log;
  error_log /var/log/nginx/error.log;
  gzip on;
  include /etc/nginx/conf.d/*.conf;
  include /etc/nginx/sites-enabled/*;
}
"
        if ! echo "${V_NGINX_CONFIG}" | tee /etc/nginx/nginx.conf; then
            echo "Can't configure nginx!" && exit 1
        fi
        service nginx start

        # PHP
        export V_PHP_VERSION="8.3"
        echo "Installing PHP ${V_PHP_VERSION} & extensions"
        add-apt-repository ppa:ondrej/php -y \
            && apt update -qq -y \
            && apt install -qq -y "php${V_PHP_VERSION}" "php${V_PHP_VERSION}-fpm" "php${V_PHP_VERSION}-mbstring" "php${V_PHP_VERSION}-mcrypt" \
                "php${V_PHP_VERSION}-gd" "php${V_PHP_VERSION}-xml" "php${V_PHP_VERSION}-curl" "php${V_PHP_VERSION}-gettext" "php${V_PHP_VERSION}-zip" \
                "php${V_PHP_VERSION}-bcmath" "php${V_PHP_VERSION}-soap" "php${V_PHP_VERSION}-redis" "php${V_PHP_VERSION}-sqlite3" \
                "php${V_PHP_VERSION}-ssh2" "php${V_PHP_VERSION}-intl"
        if [[ ! -f "/etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf" ]]; then
            echo "Error installing PHP ${V_PHP_VERSION}" && exit 1
        fi
        cp "/etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf" "/etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf.bak"
        mv "/etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf" "/etc/php/${V_PHP_VERSION}/fpm/pool.d/${V_USERNAME}.conf"
        sed -i "s|^user\ =\ www-data|user\ =\ ${V_USERNAME}|g" "/etc/php/${V_PHP_VERSION}/fpm/pool.d/${V_USERNAME}.conf"
        sed -i "s|^group\ =\ www-data|group\ =\ ${V_USERNAME}|g" "/etc/php/${V_PHP_VERSION}/fpm/pool.d/${V_USERNAME}.conf"
        sed -i "s|^\[www\]|\[${V_USERNAME}\]|g" "/etc/php/${V_PHP_VERSION}/fpm/pool.d/${V_USERNAME}.conf"
        sed -i "s|^listen\ =\ \/run\/php\/php${V_PHP_VERSION}-fpm.sock|listen\ =\ \/run\/php\/php${V_PHP_VERSION}-fpm.${V_USERNAME}.sock|g" "/etc/php/${V_PHP_VERSION}/fpm/pool.d/${V_USERNAME}.conf"
        cp "/lib/systemd/system/php${V_PHP_VERSION}-fpm.service" "/lib/systemd/system/php${V_PHP_VERSION}-fpm.service.bak"
        systemctl enable "php${V_PHP_VERSION}-fpm"
        systemctl start "php${V_PHP_VERSION}-fpm"

        # Composer
        curl -sS https://getcomposer.org/installer -o composer-setup.php
        php composer-setup.php --install-dir=/usr/local/bin --filename=composer
        rm -f composer-setup.php

        # Setup website
        echo "Setup VitoDeploy website"
        export COMPOSER_ALLOW_SUPERUSER=1
        export V_REPO="https://github.com/vitodeploy/vito.git"
        export V_VHOST_CONFIG="server {
  listen 80;
  listen [::]:80;
  http2 off;
  server_name _;

  add_header X-Frame-Options \"SAMEORIGIN\";
  add_header X-Content-Type-Options \"nosniff\";
  add_header X-XSS-Protection \"1; mode=block\" always;

  root /home/${V_USERNAME}/vito/public;
  index index.php;

  charset utf-8;

  location / {
    try_files \$uri \$uri/ /index.php?\$query_string;
  }

  location = /favicon.ico { access_log off; log_not_found off; }
  location = /robots.txt  { access_log off; log_not_found off; }

  error_page 404 /index.php;

  location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php${V_PHP_VERSION}-fpm.${V_USERNAME}.sock;
    fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_hide_header X-Powered-By;
  }

  location ~ /\.(?!well-known).* {
    deny all;
  }
}
"
        rm -rf "/home/${V_USERNAME}/vito"
        mkdir -p "/home/${V_USERNAME}/vito"
        chown -R "${V_USERNAME}:${V_USERNAME}" "/home/${V_USERNAME}/vito"
        chmod -R 755 "/home/${V_USERNAME}/vito"
        rm /etc/nginx/sites-available/default
        rm /etc/nginx/sites-enabled/default
        echo "${V_VHOST_CONFIG}" | tee /etc/nginx/sites-available/vito
        ln -s /etc/nginx/sites-available/vito /etc/nginx/sites-enabled/
        rm -rf "/home/${V_USERNAME}/vito"
        git config --global core.fileMode false
        git clone -b "${VITO_VERSION}" "${V_REPO}" "/home/${V_USERNAME}/vito"
        find "/home/${V_USERNAME}/vito" -type d -exec chmod 755 {} \;
        find "/home/${V_USERNAME}/vito" -type f -exec chmod 644 {} \;
        cd "/home/${V_USERNAME}/vito" && git config core.fileMode false
        cd "/home/${V_USERNAME}/vito" || exit 1
        # Check for the latest released tag
        V_GIT_BRANCH=$(git tag -l --merged "${VITO_VERSION}" --sort=-v:refname | head -n 1)
        git checkout "${V_GIT_BRANCH}"
        composer install --no-dev
        cp .env.prod .env
        sed -i "s/APP_URL=/APP_URL=\"${V_APP_URL}\"/g" .env
        touch "/home/${V_USERNAME}/vito/storage/database.sqlite"
        touch "/home/${V_USERNAME}/vito/storage/logs/laravel.log"
        php artisan key:generate
        php artisan storage:link
        php artisan migrate --force
        php artisan user:create "${V_USERNAME}" "${V_ADMIN_EMAIL}" "${V_ADMIN_PASSWORD}"
        openssl genpkey -algorithm RSA -out "/home/${V_USERNAME}/vito/storage/ssh-private.pem"
        chmod 600 "/home/${V_USERNAME}/vito/storage/ssh-private.pem"
        ssh-keygen -y -C "${V_ADMIN_EMAIL}" -f "/home/${V_USERNAME}/vito/storage/ssh-private.pem" > "/home/${V_USERNAME}/vito/storage/ssh-public.key"

        # fix permission
        chown -hR "${V_USERNAME}:${V_USERNAME}" "/home/${V_USERNAME}"

        # optimize
        php artisan optimize
        php artisan icons:cache
        php artisan filament:optimize
        php artisan filament:cache-components

        # Setup supervisor
        export V_WORKER_CONFIG="[program:worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/${V_USERNAME}/vito/artisan queue:work --sleep=3 --backoff=0 --queue=default,ssh,ssh-long --timeout=3600 --tries=1
autostart=1
autorestart=1
user=${V_USERNAME}
redirect_stderr=true
stdout_logfile=/home/${V_USERNAME}/.logs/workers/worker.log
stopwaitsecs=3600
"
        apt install -qq -y supervisor \
            && service supervisor enable \
            && service supervisor start
        mkdir -p "/home/${V_USERNAME}/.logs"
        mkdir -p "/home/${V_USERNAME}/.logs/workers"
        touch "/home/${V_USERNAME}/.logs/workers/worker.log"
        echo "${V_WORKER_CONFIG}" | tee /etc/supervisor/conf.d/worker.conf
        supervisorctl reread
        supervisorctl update

        # Setup cronjobs
        echo '0 */6 * * * /usr/bin/certbot renew --quiet --renew-hook "/usr/sbin/service nginx reload -s"' | crontab -
        echo "* * * * * cd /home/${V_USERNAME}/vito && php artisan schedule:run >> /dev/null 2>&1" | sudo -u "${V_USERNAME}" crontab -

        # Start worker
        supervisorctl start worker:*

        # Restart PHP-FPM & Nginx
        service "php${V_PHP_VERSION}-fpm" restart
        service nginx restart
    }

    # Print info
    function vitodeploy_print_info() {
        echo "🎉 Congratulations! Your VitoDeploy is ready."
        echo ""
        echo "🖥️  Here are your login credentials:"
        echo "✅ SSH User: ${V_USERNAME}"
        echo "✅ SSH Password: ${V_PASSWORD}"
        echo "✅ Admin Email: ${V_ADMIN_EMAIL}"
        echo "✅ Admin Password: ${V_ADMIN_PASSWORD}"
        echo "🌏 Admin Login Page: ${V_APP_URL}/login"
    }

    # Reset functions
    function vitodeploy_reset() {
        unset -f vitodeploy_header check_root_access check_supported_os vitodeploy_input vitodeploy_install vitodeploy_print_info vitodeploy_do_install \
            terminate_cleanup vitodeploy_reset get_ip_private get_ip_public validate_domain_name validate_email_address install_prerequisites
    }

    # Handle termination signal
    function vitodeploy_terminate_cleanup() {
        echo ""
        kill -term $$
        exit 0
    }

    function vitodeploy_do_install() {
        export VITO_VERSION="2.x"
        export DEBIAN_FRONTEND=noninteractive
        export NEEDRESTART_MODE=a
        export OS_DISTRIB_NAME && OS_DISTRIB_NAME=$(lsb_release -is)
        export OS_RELEASE_NAME && OS_RELEASE_NAME=$(lsb_release -cs)

        # Trap termination signal
        trap vitodeploy_terminate_cleanup SIGTSTP
        trap vitodeploy_terminate_cleanup SIGINT

        vitodeploy_header
        check_root_access "$@"
        check_supported_os
        echo "Starting VitoDeploy installation..."
        echo -e "Please ensure that you're on a fresh Ubuntu install!\n"
        if [[ "${NONINTERACTIVE}" != y* && "${NONINTERACTIVE}" != Y* ]]; then
            read -t 600 -rp "Press [Enter] to continue or [Ctrl+C] to cancel..." < /dev/tty
        else
            sleep 2 &
            wait # Wait for termination signal (ctrl+z / ctrl+c)
        fi
        vitodeploy_header
        echo -e "Please, enter required information below!\n"
        export V_INPUT_RETRY=0
        until vitodeploy_input; do
            ((V_INPUT_RETRY++))
        done
        vitodeploy_header
        vitodeploy_install
        vitodeploy_header
        vitodeploy_print_info
        vitodeploy_reset
    }

    # Start VitoDeploy installation
    vitodeploy_do_install "$@"

} # This ensures the entire script is downloaded #
