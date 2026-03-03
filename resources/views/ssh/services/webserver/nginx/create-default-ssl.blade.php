sudo mkdir -p /etc/nginx/ssl

sudo openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/default.key \
    -out /etc/nginx/ssl/default.crt \
    -subj "/CN=default"

sudo chmod 600 /etc/nginx/ssl/default.key
sudo chmod 644 /etc/nginx/ssl/default.crt
