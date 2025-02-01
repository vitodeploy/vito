@if ($site->activeSsl && $site->force_ssl)
    server {
        listen 80;
        server_name {{ $site->domain }} {{ $site->getAliasesString() }};
        return 301 https://$host$request_uri;
    }
@endif

@if ($site->type === \App\Enums\SiteType::LOAD_BALANCER)
    upstream backend {
        @switch($site->type_data['method'] ?? \App\Enums\LoadBalancerMethod::ROUND_ROBIN)
            @case(\App\Enums\LoadBalancerMethod::LEAST_CONNECTIONS)
                least_conn;
                @break
            @case(\App\Enums\LoadBalancerMethod::IP_HASH)
                ip_hash;
                @break
            @default
        @endswitch
        @if ($site->loadBalancerServers()->count() > 0)
            @foreach($site->loadBalancerServers as $server)
                server {{ $server->ip }}:{{ $server->port }} {{ $server->backup ? 'backup' : '' }} {{ $server->weight ? 'weight='.$server->weight : '' }};
            @endforeach
        @else
            server 127.0.0.1;
        @endif
    }
@endif

server {
    @if (!$site->activeSsl || !$site->force_ssl)
        listen 80;
    @endif
    @if ($site->activeSsl)
        listen 443 ssl;
        ssl_certificate {{ $site->activeSsl->certificate_path }};
        ssl_certificate_key {{ $site->activeSsl->pk_path }};
    @endif

    server_name {{ $site->domain }} {{ $site->getAliasesString() }};
    root {{ $site->getWebDirectoryPath() }};

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.php;

    charset utf-8;

    @if ($site->type()->language() === 'php')
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

    @if ($site->type === \App\Enums\SiteType::LOAD_BALANCER)
        location / {
            proxy_pass http://backend$request_uri;
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
}
