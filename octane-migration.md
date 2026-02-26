# Migration Plan: Laravel Octane with FrankenPHP (v4.x)

## Overview

Migrate Vito from nginx+php-fpm to Laravel Octane with FrankenPHP for both Docker and bare metal deployments, targeting a new 4.x major version.

### Key Decisions

- **Docker:** Full replacement with FrankenPHP image (no nginx+php-fpm fallback)
- **Bare Metal:** FrankenPHP behind nginx (nginx as reverse proxy for SSL/certbot)
- **Version:** New 4.x major version branch
- **Worker Mode:** Full Octane worker mode (persistent PHP processes)
- **FrankenPHP Binary:** Managed by Laravel Octane (`octane:install`)

---

## Phase 1: Code Compatibility for Octane

**Status:** `completed`

Ensure the codebase is safe for Octane's persistent worker model (no memory leaks, no stale state between requests).

### 1.1 Add Laravel Octane Package

- **Status:** `completed`
- **File:** `composer.json`
- **Action:** Add `"laravel/octane": "^2.9"` to require

### 1.2 Publish Octane Config

- **Status:** `completed`
- **File:** `config/octane.php`
- **Action:** Run `php artisan octane:install --server=frankenphp` or create manually
- **Notes:** Configure FrankenPHP driver, workers count, max requests, etc.

### 1.3 Fix Octane Memory Leak Risks

- **Status:** `completed`
- **Requires Exploration:** Yes - need to verify all stateful code patterns
- **Audit Results:**
  - `GetPluginInstance`: Cleared via OctaneServiceProvider on RequestTerminated
  - `Agent` helper: Only used in tests, not a concern
  - Plugin registrations: Happen during boot only, safe for Octane

#### 1.3.1 GetPluginInstance Class

- **File:** `app/Actions/Plugins/GetPluginInstance.php`
- **Issue:** Stores plugin instances in `$implementations` array (line 13)
- **Current Binding:** `scoped()` in `PluginsServiceProvider` (resets per request)
- **Action:** Add Octane `RequestTerminated` listener to call `clear()` method
- **Risk Level:** Medium

#### 1.3.2 Agent Helper Class

- **File:** `app/Helpers/Agent.php`
- **Issue:** Instance stores detection results in `$store` array (line 53)
- **Current Binding:** Not bound in container (instantiated directly)
- **Action:** Verify usage patterns; may need to ensure fresh instance per request
- **Risk Level:** Low

#### 1.3.3 Full Codebase Audit

- **Status:** `pending`
- **Requires Exploration:** Yes
- **Action:** Search for:
  - Static properties that accumulate data
  - Singletons storing request-specific data
  - Global state modifications
  - File handles or connections not properly closed

### 1.4 Create Octane Service Provider

- **Status:** `completed`
- **File:** `app/Providers/OctaneServiceProvider.php`
- **Action:** Create provider with Octane lifecycle listeners

```php
<?php

namespace App\Providers;

use App\Actions\Plugins\GetPluginInstance;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;

class OctaneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['events']->listen(RequestTerminated::class, function () {
            // Flush plugin instance cache
            if ($this->app->bound(GetPluginInstance::class)) {
                $this->app->make(GetPluginInstance::class)->clear();
            }
        });
    }
}
```

### 1.5 Register OctaneServiceProvider

- **Status:** `completed`
- **File:** `config/app.php`
- **Action:** Add `App\Providers\OctaneServiceProvider::class`

### 1.6 Add Health Check Endpoint

- **Status:** `completed`
- **File:** `app/Http/Controllers/API/HealthController.php`
- **Action:** Added `/up` endpoint for container orchestration (existing `/api/health` kept)

---

## Phase 2: Docker Migration

**Status:** `completed`

Replace the existing nginx+php-fpm Docker setup with FrankenPHP.

### 2.1 New Dockerfile with FrankenPHP

- **Status:** `completed`
- **File:** `docker/Dockerfile`
- **Action:** Replace entire file with FrankenPHP-based image
- **Requires Confirmation:** Yes - review base image choice (`dunglas/frankenphp` vs building from scratch)

```dockerfile
FROM dunglas/frankenphp:latest-php8.4

WORKDIR /var/www/html

# Install PHP extensions
RUN install-php-extensions \
    intl \
    mbstring \
    xml \
    curl \
    zip \
    bcmath \
    gd \
    redis \
    sqlite3 \
    pcntl \
    opcache \
    ssh2

# Install additional tools
RUN apt-get update && apt-get install -y \
    git unzip supervisor redis-server openssh-client cron \
    && rm -rf /var/lib/apt/lists/*

# Copy application
COPY . /var/www/html
RUN rm -rf /var/www/html/.git /var/www/html/vendor /var/www/html/node_modules
RUN composer install --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Cron
RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

# Supervisor for Horizon
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80 443

CMD ["/start.sh"]
```

### 2.2 Update Docker start.sh

- **Status:** `completed`
- **File:** `docker/start.sh`
- **Action:** Replace nginx+fpm startup with Octane

```bash
#!/bin/bash

# ... (keep existing init logic for SSH keys, database.sqlite, APP_KEY validation)

chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Start Redis
service redis-server start

# Run migrations and optimize
php /var/www/html/artisan migrate --force
php /var/www/html/artisan optimize:clear
php /var/www/html/artisan optimize

# Create user if needed
php /var/www/html/artisan user:create "$NAME" "$EMAIL" "$PASSWORD"

# Start cron
cron

# Start Supervisor (Horizon) in background
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf &

echo "Vito is running with Octane!"

# Start Octane (foreground - this keeps container alive)
exec php /var/www/html/artisan octane:start --server=frankenphp --host=0.0.0.0 --port=80
```

### 2.3 Update Docker supervisord.conf

- **Status:** `completed`
- **File:** `docker/supervisord.conf`
- **Action:** Remove nginx/php-fpm programs, keep Horizon only

```ini
[supervisord]
nodaemon=false
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:horizon]
user=root
autostart=true
autorestart=true
command=/usr/bin/php /var/www/html/artisan horizon
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
stopwaitsecs=3600
```

### 2.4 Delete Docker nginx.conf

- **Status:** `completed`
- **File:** `docker/nginx.conf`
- **Action:** Delete file (no longer needed)

### 2.5 Update docker-compose.yml

- **Status:** `completed`
- **File:** `docker/docker-compose.yml`
- **Action:** No changes needed - port mappings remain compatible (8000:80)

### 2.6 Update Docker php.ini

- **Status:** `completed`
- **File:** `docker/php.ini`
- **Action:** Added Octane-specific opcache settings and realpath cache tuning
- **Notes:** FrankenPHP uses `/usr/local/etc/php/conf.d/` for PHP configuration

---

## Phase 3: Bare Metal Migration

**Status:** `completed`

Update the install and upgrade scripts for bare metal deployments.

### 3.1 Create Upgrade Script (3.x to 4.x)

- **Status:** `completed`
- **File:** `scripts/upgrade-3x-to-4x.sh`
- **Action:** Create new migration script
- **Requires Confirmation:** Yes - review nginx proxy config and supervisor setup

```bash
#!/bin/bash

echo "Upgrading Vito from 3.x to 4.x (Octane + FrankenPHP)"

cd /home/vito/vito

# Backup current state
echo "Creating backup..."
cp /etc/nginx/sites-available/vito /etc/nginx/sites-available/vito.bak
cp /etc/supervisor/conf.d/worker.conf /etc/supervisor/conf.d/worker.conf.bak

# Fetch and checkout 4.x
echo "Fetching 4.x branch..."
git fetch --all
git checkout 4.x

# Install dependencies (includes laravel/octane)
echo "Installing dependencies..."
composer install --no-dev

# Install FrankenPHP binary via Octane
echo "Installing FrankenPHP..."
php artisan octane:install --server=frankenphp

# Update nginx config (proxy to Octane)
echo "Updating nginx configuration..."
sudo tee /etc/nginx/sites-available/vito << 'EOF'
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

sudo nginx -t && sudo service nginx reload

# Update supervisor configuration
echo "Updating supervisor configuration..."
sudo tee /etc/supervisor/conf.d/vito.conf << 'EOF'
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
process_name=%(program_name)s_%(process_num)02d
command=php /home/vito/vito/artisan horizon
autostart=true
autorestart=true
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/horizon.log
stopwaitsecs=3600
EOF

# Remove old worker config
sudo rm -f /etc/supervisor/conf.d/worker.conf

# Stop php-fpm (no longer needed for Vito itself)
echo "Stopping php-fpm for Vito..."
# Note: Don't disable php-fpm entirely as it may be used by managed sites
# sudo systemctl disable php8.4-fpm

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

echo "✅ Upgraded to 4.x with Octane + FrankenPHP!"
echo ""
echo "If you encounter issues, run: bash /home/vito/vito/scripts/rollback-4x-to-3x.sh"
```

### 3.2 Update Install Script for 4.x

- **Status:** `completed`
- **File:** `scripts/install.sh`
- **Action:** Modify to use Octane instead of php-fpm for Vito
- **Requires Confirmation:** Yes - significant changes to install flow

Key changes needed:
- Keep PHP CLI installation (needed for artisan, composer)
- Keep php-fpm installation (needed for managed sites on same server)
- Add `php artisan octane:install --server=frankenphp`
- Change nginx vhost to proxy config
- Change supervisor to run Octane instead of referencing php-fpm

### 3.3 Update update.sh Script

- **Status:** `completed`
- **File:** `scripts/update.sh`
- **Action:** Add Octane reload/restart after updates

```bash
# Add after "php artisan optimize"
echo "Restarting Octane..."
sudo supervisorctl restart octane

echo "Restarting Horizon..."
sudo supervisorctl restart horizon
```

### 3.4 Rollback Script

- **Status:** `cancelled`
- **Decision:** Rollback from 4.x to 3.x is not supported. Users should backup before upgrading.

---

## Phase 4: CI/CD & Release Updates

**Status:** `completed`

Update GitHub workflows and release process for 4.x.

### 4.1 Update Docker Release Workflow

- **Status:** `completed`
- **File:** `.github/workflows/docker-release.yml`
- **Action:** Add 4.x branch handling

```yaml
# Add to tag logic
elif [[ "$COMMIT_REF" == 4.x* ]]; then
  TAGS="$TAGS,vitodeploy/vito:4.x"
  if [[ "$IS_PRERELEASE" == "false" ]]; then
    TAGS="$TAGS,vitodeploy/vito:latest"
  fi
fi
```

### 4.2 Update Version Pattern in update.sh

- **Status:** `completed`
- **File:** `scripts/update.sh`
- **Action:** Changed version pattern from `3.x` to `4.x` (completed in Phase 3)

### 4.3 Create 4.x Branch

- **Status:** `pending`
- **Action:** Create new `4.x` branch from `3.x` after all changes
- **Requires Confirmation:** Yes - timing of branch creation

---

## Phase 5: Documentation & Testing

**Status:** `pending`

### 5.1 Update README/Docs

- **Status:** `pending`
- **Action:** Document new requirements and upgrade path
- **Requires Confirmation:** Yes - documentation location and format

### 5.2 Test Docker Build

- **Status:** `pending`
- **Action:** Build and test Docker image locally
- **Commands:**
  ```bash
  cd docker
  docker build -t vito:4.x-test -f Dockerfile ..
  docker run -e APP_KEY=base64:... -e EMAIL=test@test.com -p 8000:80 vito:4.x-test
  ```

### 5.3 Test Bare Metal Upgrade

- **Status:** `pending`
- **Action:** Test upgrade script on a 3.x installation
- **Requires Exploration:** Yes - need test environment

### 5.4 Memory Leak Testing

- **Status:** `pending`
- **Action:** Run load tests and monitor memory usage
- **Requires Exploration:** Yes - need load testing setup

### 5.5 Plugin Compatibility Testing

- **Status:** `pending`
- **Action:** Test all bundled plugins work correctly under Octane
- **Requires Exploration:** Yes - list all plugins and test each

---

## Summary: Items Requiring Confirmation

| Phase | Item | Reason |
|-------|------|--------|
| 1.3.3 | Full codebase audit | Need to verify all stateful patterns |
| 3.1 | Upgrade script | Review nginx and supervisor configs |
| 3.2 | Install script changes | Significant modifications needed |
| 4.3 | Branch creation timing | When to create 4.x branch |
| 5.1 | Documentation | Where and how to document |
| 5.3 | Bare metal testing | Need test environment |
| 5.4 | Memory leak testing | Need load testing setup |
| 5.5 | Plugin testing | Need to test each plugin |

---

## Summary: Items Requiring Exploration

| Phase | Item | What to Explore |
|-------|------|-----------------|
| 1.3 | Stateful code patterns | Search for singletons, static state, global variables |
| 5.3 | Test environment | Set up or identify 3.x test server |
| 5.4 | Load testing | Tools and thresholds for memory testing |
| 5.5 | Plugin list | Enumerate all plugins to test |

---

## Files to Create/Modify Summary

| Action | File | Phase |
|--------|------|-------|
| Modify | `composer.json` | 1.1 |
| Create | `config/octane.php` | 1.2 |
| Create | `app/Providers/OctaneServiceProvider.php` | 1.4 |
| Modify | `bootstrap/providers.php` | 1.5 |
| Modify | `routes/web.php` | 1.6 |
| Modify | `docker/Dockerfile` | 2.1 |
| Modify | `docker/start.sh` | 2.2 |
| Modify | `docker/supervisord.conf` | 2.3 |
| Delete | `docker/nginx.conf` | 2.4 |
| Modify | `docker/docker-compose.yml` | 2.5 |
| Create | `scripts/upgrade-3x-to-4x.sh` | 3.1 |
| Modify | `scripts/install.sh` | 3.2 |
| Modify | `scripts/update.sh` | 3.3 |
| Modify | `.github/workflows/docker-release.yml` | 4.1 |

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Memory leaks from stateful code | Medium | High | Octane listeners, thorough testing |
| Plugin compatibility issues | Medium | Medium | Document requirements, test all plugins |
| SSL certificate renewal breaks | Low | High | Keep nginx for SSL, certbot unchanged |
| Failed bare metal upgrades | Medium | High | Backup before upgrading, clear docs |
| FrankenPHP binary issues | Low | Medium | Octane handles download, fallback docs |
| Docker image size increase | Low | Low | Multi-stage build if needed |

---

## Timeline Estimate

| Phase | Estimated Effort |
|-------|------------------|
| Phase 1: Code Compatibility | 1-2 days |
| Phase 2: Docker Migration | 1 day |
| Phase 3: Bare Metal Migration | 1-2 days |
| Phase 4: CI/CD Updates | 0.5 day |
| Phase 5: Testing | 2-3 days |
| **Total** | **5-8 days** |
