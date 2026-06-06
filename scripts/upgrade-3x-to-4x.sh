#!/bin/bash
#
# Upgrade Vito from 3.x to 4.x (bare-metal install at /home/vito/vito).
#
# Usage:
#   sudo bash scripts/upgrade-3x-to-4x.sh            # latest STABLE 4.x
#   sudo bash scripts/upgrade-3x-to-4x.sh --beta     # latest beta/rc (REQUIRED while 4.x is in beta)
#   sudo bash scripts/upgrade-3x-to-4x.sh --alpha    # latest alpha/beta/rc
#
set -uo pipefail

echo "Upgrading Vito from 3.x to 4.x"

VITO_DIR="/home/vito/vito"
NGINX_VHOST="/etc/nginx/sites-available/vito"

# composer install may run as root during this upgrade
export COMPOSER_ALLOW_SUPERUSER=1

echo "Getting started"
cd "${VITO_DIR}" || { echo "❌ ${VITO_DIR} not found — is Vito installed here?" >&2; exit 1; }

# Always hand the install back to the vito user, even on early exit
# (composer/git run as root during the upgrade).
trap 'sudo chown -R vito:vito "${VITO_DIR}" 2>/dev/null || true' EXIT

# Discard local changes so 'git checkout 4.x' can't fail (update.sh hard-resets too).
# .env, storage/ and other ignored files are preserved — git clean does not touch ignored files.
git reset --hard HEAD
git clean -fd
sudo chown -R vito:vito "${VITO_DIR}"

# --- WebSocket nginx proxy (the only server-topology change vs 3.x) ---
if [ -f "${NGINX_VHOST}" ]; then
  if ! grep -q "location /ws/" "${NGINX_VHOST}"; then
    echo "Configuring Nginx for websocket support..."
    sudo cp "${NGINX_VHOST}" "${NGINX_VHOST}.pre-4x.bak"
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
    }' "${NGINX_VHOST}"

    if ! grep -q "location /ws/" "${NGINX_VHOST}"; then
      echo "❌ Could not insert the /ws/ block into ${NGINX_VHOST}; restoring backup. Add it manually." >&2
      sudo cp "${NGINX_VHOST}.pre-4x.bak" "${NGINX_VHOST}"
      exit 1
    fi

    if sudo nginx -t; then
      sudo service nginx restart
    else
      echo "❌ nginx config test failed after edit; restoring backup." >&2
      sudo cp "${NGINX_VHOST}.pre-4x.bak" "${NGINX_VHOST}"
      sudo service nginx restart || true
      exit 1
    fi
  fi
else
  echo "⚠️  ${NGINX_VHOST} not found. Manually add a '/ws/' -> 127.0.0.1:8085 proxy to your Vito vhost so websockets/terminal work." >&2
fi

# --- WebSocket supervisor worker ---
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
  sudo supervisorctl start websocket 2>/dev/null || true
fi

# --- Switch to 4.x and run the standard updater (forward channel flags, e.g. --beta) ---
echo "Fetching the latest release"
git fetch --all --tags
git checkout 4.x || { echo "❌ Failed to checkout the 4.x branch." >&2; exit 1; }

if ! bash scripts/update.sh "$@"; then
  echo "❌ update.sh failed. If 4.x is still in beta, re-run with: sudo bash scripts/upgrade-3x-to-4x.sh --beta" >&2
  exit 1
fi

echo "✅ Upgrade to 4.x complete."
