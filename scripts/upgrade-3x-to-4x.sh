#!/bin/bash

echo "
 __      ___ _        _____             _
 \ \    / (_) |      |  __ \           | |
  \ \  / / _| |_ ___ | |  | | ___ _ __ | | ___  _   _
   \ \/ / | | __/ _ \| |  | |/ _ \ '_ \| |/ _ \| | | |
    \  /  | | || (_) | |__| |  __/ |_) | | (_) | |_| |
     \/   |_|\__\___/|_____/ \___| .__/|_|\___/ \__, |
                                 | |             __/ |
                                 |_|            |___/

Upgrading from 3.x to 4.x (Octane + FrankenPHP)
"

set -e

cd /home/vito/vito

# Pre-flight checks
echo "Running pre-flight checks..."

if [ ! -f /etc/nginx/sites-available/vito ]; then
    echo "Error: Vito nginx configuration not found. Is Vito installed?"
    exit 1
fi

if ! command -v supervisorctl &> /dev/null; then
    echo "Error: Supervisor is not installed."
    exit 1
fi

# Create logs directory if it doesn't exist
mkdir -p /home/vito/.logs

# Stop current workers
echo "Stopping current workers..."
sudo supervisorctl stop worker:* || true

# Fetch and checkout 4.x
echo "Fetching 4.x branch..."
git fetch --all
git checkout 4.x

# Get latest 4.x tag
INCLUDE_PATTERN='^4\.[0-9]+\.[0-9]+$'
MATCHING_TAGS=$(git tag | grep -E "$INCLUDE_PATTERN" | sort -V)
NEW_RELEASE=$(echo "$MATCHING_TAGS" | tail -n 1)

if [[ -n "$NEW_RELEASE" ]]; then
    echo "Switching to tag: $NEW_RELEASE"
    git checkout "$NEW_RELEASE"
fi

# Install dependencies (includes laravel/octane)
echo "Installing composer dependencies..."
composer install --no-dev

# Install FrankenPHP binary via Octane
echo "Installing FrankenPHP..."
php artisan octane:install --server=frankenphp --no-interaction

# Update nginx config (proxy to Octane)
echo "Updating nginx configuration..."
sudo tee /etc/nginx/sites-available/vito > /dev/null << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;

    client_max_body_size 100M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
    }
}
EOF

# Test and reload nginx
echo "Testing nginx configuration..."
sudo nginx -t
sudo service nginx reload

# Update supervisor configuration
echo "Updating supervisor configuration..."
sudo tee /etc/supervisor/conf.d/vito.conf > /dev/null << 'EOF'
[program:octane]
process_name=%(program_name)s
command=php /home/vito/vito/artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8000
autostart=true
autorestart=true
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/octane.log
stopwaitsecs=10

[program:horizon]
process_name=%(program_name)s
command=php /home/vito/vito/artisan horizon
autostart=true
autorestart=true
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/horizon.log
stopwaitsecs=3600
EOF

# Create log files with correct permissions
touch /home/vito/.logs/octane.log
touch /home/vito/.logs/horizon.log
chown -R vito:vito /home/vito/.logs

# Remove old worker config
echo "Removing old supervisor configuration..."
sudo rm -f /etc/supervisor/conf.d/worker.conf

# Reload supervisor
echo "Reloading supervisor..."
sudo supervisorctl reread
sudo supervisorctl update

# Run migrations
echo "Running migrations..."
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

# Start services
echo "Starting Octane and Horizon..."
sudo supervisorctl start octane
sudo supervisorctl start horizon

# Verify services are running
sleep 2
echo "Verifying services..."
if sudo supervisorctl status octane | grep -q "RUNNING"; then
    echo "Octane is running"
else
    echo "Warning: Octane may not have started correctly"
    sudo supervisorctl status octane
fi

if sudo supervisorctl status horizon | grep -q "RUNNING"; then
    echo "Horizon is running"
else
    echo "Warning: Horizon may not have started correctly"
    sudo supervisorctl status horizon
fi

echo ""
echo "Upgraded to 4.x with Octane + FrankenPHP!"
