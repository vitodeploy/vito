server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;

    root /var/www/vito-splash;
    index index.html;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        expires 1h;
        add_header Cache-Control "public, max-age=3600" always;
        try_files $uri $uri/ /index.html;
    }
}
