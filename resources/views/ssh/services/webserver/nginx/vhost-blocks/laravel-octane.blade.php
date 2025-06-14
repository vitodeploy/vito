#[laravel-octane]
index index.php index.html;
error_page 404 /index.php;
location /index.php {
    try_files /not_exists @octane;
}
location / {
    try_files $uri $uri/ @octane;
}
location @octane {
    set $suffix "";
    if ($uri = /index.php) {
        set $suffix ?$query_string;
    }
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
    proxy_pass http://127.0.0.1:{{ data_get($site->type_data, 'octane_port', 8000) }}$suffix;
}
#[/laravel-octane]
