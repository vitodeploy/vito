@if ($site->activeSsl && $site->force_ssl)
    server {
        listen 80;
        server_name {{ $site->domain }} {{ $site->getAliasesString() }};
        return 301 https://$host$request_uri;
    }
@endif

server {
    @if (!$site->activeSsl || !$site->force_ssl)
        listen 80;
    @endif
    @if ($site->activeSsl)
        listen 443 ssl;
        ssl_certificate {{ $site->activeSsl->getCertificatePath() }};
        ssl_certificate_key {{ $site->activeSsl->getPkPath() }};
    @endif

    server_name {{ $site->domain }} {{ $site->getAliasesString() }};
    root {{ $site->getWebDirectoryPath() }};

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.php;

    charset utf-8;

    @if ($site->port)
        location / {
            try_files $uri $uri/ /index.html;
        }
        location / {
            proxy_pass http://127.0.0.1:{{ $site->port }}/;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            proxy_set_header X-Forwarded-For $remote_addr;
        }
    @elseif ($site->php_version)
        @php
            $phpSocket = 'unix:/var/run/php/php-fpm.sock';
            if ($site->isIsolated()) {
                $phpSocket = "unix:/run/php/php{$site->php_version}-fpm-{$site->user}.sock";
            }
        @endphp
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
        location ~ \.php$ {
            fastcgi_pass {{ $phpSocket }};
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_hide_header X-Powered-By;
        }
    @endif

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.html;

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
