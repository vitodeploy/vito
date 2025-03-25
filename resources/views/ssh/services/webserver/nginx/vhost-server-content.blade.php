@if (!$ssl)
    listen 80;
@endif
@if ($ssl)
    listen 443 ssl;
    ssl_certificate {{ $ssl->certificate_path }};
    ssl_certificate_key {{ $ssl->pk_path }};
@endif

server_name {{ implode(' ', $domains) }};
root {{ $site->getWebDirectoryPath() }};

add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";

index index.html index.php;

charset utf-8;

@if ($site->type()->language() === 'php')
    @php
        $phpSocket = "unix:/var/run/php/php{$site->php_version}-fpm.sock";
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

@if ($site->type === \App\Enums\SiteType::LOAD_BALANCER)
    location / {
        proxy_pass http://{{ $backendName }}$request_uri;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
@endif

location = /favicon.ico { access_log off; log_not_found off; }
location = /robots.txt  { access_log off; log_not_found off; }

error_page 404 /index.html;

location ~ /\.(?!well-known).* {
    deny all;
}