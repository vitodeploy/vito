export DEBIAN_FRONTEND=noninteractive

sudo apt-get install apache2 -y

# install certbot
sudo apt-get install certbot python3-certbot-apache -y

# run PHP through PHP-FPM, not mod_php
sudo a2dismod mpm_prefork 2>/dev/null || true
sudo a2enmod mpm_event

sudo a2enmod proxy proxy_http proxy_fcgi proxy_wstunnel proxy_balancer lbmethod_byrequests lbmethod_bybusyness rewrite ssl headers setenvif

# run apache as the deploy user so it can read site files and FPM sockets
sudo sed -i "s/^export APACHE_RUN_USER=.*/export APACHE_RUN_USER={{ $user }}/" /etc/apache2/envvars
sudo sed -i "s/^export APACHE_RUN_GROUP=.*/export APACHE_RUN_GROUP={{ $user }}/" /etc/apache2/envvars

# silence the global ServerName warning
echo "ServerName localhost" | sudo tee /etc/apache2/conf-available/vito.conf > /dev/null
sudo a2enconf vito

# the packaged unit's PrivateTmp namespace breaks `systemctl reload` (226/NAMESPACE);
# ProtectHome would also hide sites served from /home. Disable both so reloads work.
sudo mkdir -p /etc/systemd/system/apache2.service.d
sudo tee /etc/systemd/system/apache2.service.d/vito-override.conf > /dev/null <<'EOF'
[Service]
PrivateTmp=false
ProtectHome=false
EOF
sudo systemctl daemon-reload

sudo mkdir -p /etc/apache2/sites-available
sudo mkdir -p /etc/apache2/sites-enabled
