#!/bin/bash

echo "Upgrading Vito from 3.x to 4.x"

echo "Getting started"
cd /home/vito/vito
git stash
sudo chown -R vito:vito /home/vito/vito

# Add websocket proxy to nginx
if ! grep -q "location /ws/" /etc/nginx/sites-available/vito; then
  echo "Configuring Nginx for websocket support..."
  sudo sed -i '/location ~ \/\\.(?!well-known).*/i \
    location /ws/ {\
        proxy_pass http://127.0.0.1:8085;\
        proxy_http_version 1.1;\
        proxy_set_header Upgrade $http_upgrade;\
        proxy_set_header Connection "upgrade";\
        proxy_set_header Host $host;\
        proxy_set_header X-Real-IP $remote_addr;\
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\
        proxy_read_timeout 86400;\
    }' /etc/nginx/sites-available/vito
  sudo service nginx restart
fi

# Add websocket supervisor configuration
if [ ! -f /etc/supervisor/conf.d/websocket.conf ]; then
  echo "Adding websocket supervisor configuration..."
  sudo mkdir -p /home/vito/.logs/workers
  sudo touch /home/vito/.logs/workers/websocket.log
  sudo chown -R vito:vito /home/vito/.logs
  echo "
[program:websocket]
process_name=%(program_name)s
command=php /home/vito/vito/artisan ws:serve
autostart=1
autorestart=1
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/workers/websocket.log
" | sudo tee /etc/supervisor/conf.d/websocket.conf
  sudo supervisorctl reread
  sudo supervisorctl update
fi

echo "Fetching the latest release"
git fetch
git checkout 4.x
bash scripts/update.sh
