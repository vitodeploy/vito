upstream {{ Str::slug($domain) }} {
@if (isset($balanceMethod))
    {{ $balanceMethod }};
@endif
@foreach($servers as $server)
    server {{ $server['host'] }}:{{ $server['port'] }}@if($server['weight']) weight={{ $server['weight'] }} @endif @if($server['backup'])backup @endif @if($server['down'])down @endif;
@endforeach
}
@if ($ssl)
server {
    listen 80;
    server_name {{ $domain }} {{ $aliases }};
    return 301 https://$host$request_uri;
}
@endif
server {
    listen @if ($ssl)443 ssl @else 80 @endif;
    server_name {{ $domain }} {{ $aliases }};
    root {{ $path }}/{{ $web_directory }};
@if($ssl)
    ssl_certificate {{ $certificate }};
    ssl_certificate_key {{ $private_key }};
@endif
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    error_page 404 /index.html;

    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_pass http://{{ Str::slug($domain) }}/;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    include conf.d/{{ $domain }}_redirects;
}
