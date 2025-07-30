#[reverse-proxy]
add_header X-Frame-Options DENY;
add_header X-Content-Type-Options nosniff;
location / {
    proxy_pass http://localhost:{{ $site->port }};
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
#[/reverse-proxy]
