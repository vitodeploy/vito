#[load-balancer]
@php
    $backendName = preg_replace("/[^A-Za-z0-9 ]/", '', $site->domain).'_backend';
@endphp
location / {
    proxy_pass http://{{ $backendName }}$request_uri;

    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $http_connection;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
error_page 404 /index.html;
#[/load-balancer]
