#[core]
server_name {{ $site->domain }} {{ $site->getAliasesString() }};
root {{ $site->getWebDirectoryPath() }};
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
charset utf-8;
access_log off;
error_log  /var/log/nginx/{{ $site->domain }}-error.log error;
location ~ /\.(?!well-known).* {
    deny all;
}
#[/core]
