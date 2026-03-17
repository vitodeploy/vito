@if ($application->activeSsl && $application->force_ssl)
server {
    listen 80;
    server_name {{ $application->domain }} {{ $application->getAliasesString() }};
    return 301 https://$host$request_uri;
}
@endif

server {
@if (!$application->activeSsl || !$application->force_ssl)
    listen 80;
    listen [::]:80;
@endif
@if ($application->activeSsl)
    listen 443 ssl;
    listen [::]:443 ssl;
    ssl_certificate {{ $application->activeSsl->certificate_path }};
    ssl_certificate_key {{ $application->activeSsl->pk_path }};
@endif

    server_name {{ $application->domain }} {{ $application->getAliasesString() }};
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    charset utf-8;
    access_log off;
    error_log  /var/log/nginx/{{ $application->domain }}-error.log error;

    location / {
        proxy_pass {{ $application->type_data['scheme'] ?? 'http' }}://{{ $application->type_data['host'] ?? 'localhost' }}:{{ $application->type_data['port'] ?? 3000 }};
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
@if (!empty($application->type_data['websocket']))
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
@endif
        proxy_cache_bypass $http_upgrade;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
