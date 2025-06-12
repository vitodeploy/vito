#[load-balancer]
@php
    $backendName = preg_replace("/[^A-Za-z0-9 ]/", '', $site->domain).'_backend';
@endphp
location / {
    proxy_pass http://{{ $backendName }}$request_uri;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
error_page 404 /index.html;
#[/load-balancer]
